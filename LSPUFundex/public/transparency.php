<?php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle  = 'Fund Transparency';
$activePage = 'transparency';

$filterDept = (int)($_GET['dept']   ?? 0);
$search     = trim($_GET['search']  ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($filterDept > 0) {
    $where   .= " AND s.department_id = ?";
    $params[] = $filterDept;
    $types   .= "i";
}
if (!empty($search)) {
    $where   .= " AND s.name LIKE ?";
    $params[] = "%{$search}%";
    $types   .= "s";
}

$sql = "SELECT s.id, s.name AS section_name, s.school_year,
               d.name AS dept_name, d.code AS dept_code,
               yl.name AS year_level_name,
               COALESCE(SUM(f.amount),0) AS total_funds,
               COALESCE(SUM(e.amount),0) AS total_expenses,
               COUNT(DISTINCT f.id)       AS fund_count,
               COUNT(DISTINCT e.id)       AS expense_count
        FROM sections s
        JOIN departments d  ON d.id  = s.department_id
        JOIN year_levels yl ON yl.id = s.year_level_id
        LEFT JOIN funds    f ON f.section_id = s.id
        LEFT JOIN expenses e ON e.section_id = s.id
        {$where}
        GROUP BY s.id
        ORDER BY d.name ASC, yl.order_num ASC, s.name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $sections = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$depts = $conn->query(
    "SELECT id, name, code FROM departments
     WHERE is_active = 1 ORDER BY name ASC"
)->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-eye me-2"></i>Fund Transparency</h1>
        <p>Complete financial overview of all sections — publicly accessible</p>
    </div>
</div>

<div class="lspu-card mb-4">
    <div class="lspu-card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search section name..."
                           value="<?php echo clean($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept" class="form-select">
                    <option value="0">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>"
                            <?php echo $filterDept == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($d['name']); ?> (<?php echo clean($d['code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-lspu">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="transparency.php" class="btn btn-outline-secondary ms-1">
                    <i class="bi bi-x me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="lspu-card">
    <div class="lspu-card-header">
        <h5><i class="bi bi-table me-2"></i>Section Financial Records</h5>
        <span class="badge bg-light text-dark"><?php echo count($sections); ?> sections</span>
    </div>
    <div class="lspu-card-body p-0">
        <div class="table-responsive">
            <table class="lspu-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Section</th>
                        <th>Department</th>
                        <th>Year Level</th>
                        <th>School Year</th>
                        <th class="text-end">Total Funds</th>
                        <th class="text-end">Total Expenses</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Records</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No sections found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($sections as $sec):
                        $balance = $sec['total_funds'] - $sec['total_expenses'];
                    ?>
                    <tr>
                        <td class="text-muted"><?php echo $i++; ?></td>
                        <td><strong><?php echo clean($sec['section_name']); ?></strong></td>
                        <td>
                            <span class="badge-balance"><?php echo clean($sec['dept_code']); ?></span>
                            <small class="text-muted ms-1"><?php echo clean($sec['dept_name']); ?></small>
                        </td>
                        <td><?php echo clean($sec['year_level_name']); ?></td>
                        <td class="text-muted small"><?php echo clean($sec['school_year']); ?></td>
                        <td class="text-end">
                            <span class="badge-fund"><?php echo formatMoney($sec['total_funds']); ?></span>
                        </td>
                        <td class="text-end">
                            <span class="badge-expense"><?php echo formatMoney($sec['total_expenses']); ?></span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold"
                                  style="color:<?php echo $balance >= 0 ? '#27ae60' : '#e74c3c'; ?>">
                                <?php echo formatMoney($balance); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-arrow-up text-success"></i>
                                <?php echo $sec['fund_count']; ?>
                                &nbsp;
                                <i class="bi bi-arrow-down text-danger"></i>
                                <?php echo $sec['expense_count']; ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>