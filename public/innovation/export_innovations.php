<?php
require_once __DIR__ . '/controllers/innovation_controller.php';

$innovationC = new InnovationController();
$all = $innovationC->listInnovations();

$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$statutSel = $_GET['statut'] ?? '';
$sortColumn = $_GET['sort'] ?? '';
$sortDir    = strtoupper($_GET['dir'] ?? 'ASC');
$allowedSort = ['titre', 'date_creation', 'statut'];

$filtered = array_filter($all, function($row) use ($search, $statutSel) {
    $ok = true;
    if ($search !== '') {
        $ok = $ok && (
            stripos($row['titre'], $search) !== false ||
            stripos($row['description'], $search) !== false
        );
    }
    if ($statutSel !== '') {
        $ok = $ok && ($row['statut'] === $statutSel);
    }
    return $ok;
});

if (in_array($sortColumn, $allowedSort)) {
    usort($filtered, function($a, $b) use ($sortColumn, $sortDir) {
        $va = $a[$sortColumn] ?? '';
        $vb = $b[$sortColumn] ?? '';
        if ($va == $vb) return 0;
        $cmp = ($va < $vb) ? -1 : 1;
        return $sortDir === 'DESC' ? -$cmp : $cmp;
    });
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=innovations.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Titre', 'Description', 'Image', 'Statut']);
foreach ($filtered as $row) {
    fputcsv($out, [
        $row['id_innovation'],
        $row['titre'],
        $row['description'],
        $row['image'],
        $row['statut'],
    ]);
}
fclose($out);
exit;


