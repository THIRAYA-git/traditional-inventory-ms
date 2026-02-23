<?php
include('../includes/header.php'); 
$pdo = connectDB();
$po_id = $_GET['id'] ?? 0;

// Fetch PO and Supplier Details
$stmt = $pdo->prepare("SELECT po.*, s.name as supplier, s.address, s.email 
                       FROM purchase_orders po 
                       JOIN suppliers s ON po.supplier_id = s.supplier_id 
                       WHERE po.po_id = ?");
$stmt->execute([$po_id]);
$invoice = $stmt->fetch();

if (!$invoice) die("Invoice data not found.");
?>
<div class="card shadow no-print mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-muted">Document Actions</h5>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fas fa-print"></i> Print
            </button>
            
            <a href="generate_pdf.php?id=<?= $id ?>&type=<?= $type ?>" class="btn btn-outline-danger me-2">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
            
            <a href="email_invoice.php?id=<?= $id ?>&type=<?= $type ?>" class="btn btn-primary">
                <i class="fas fa-envelope"></i> Send to Email
            </a>
        </div>
    </div>
</div>
<div class="content-area bg-light">
    <div class="container py-5">
        <div class="card shadow border-0 p-4">
            <div class="row mb-4">
                <div class="col-6">
                    <h2 class="text-primary fw-bold">PURCHASE INVOICE</h2>
                    <p class="mb-0"><strong>InventoSmart MS</strong></p>
                </div>
                <div class="col-6 text-end">
                    <h4 class="mb-0">INV-PUR-<?= $invoice['po_id'] ?></h4>
                    <p class="text-muted">Date: <?= date('d M Y', strtotime($invoice['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-6">
                    <p class="text-muted mb-1">BILL FROM:</p>
                    <h5><?= htmlspecialchars($invoice['supplier']) ?></h5>
                    <p class="small"><?= nl2br(htmlspecialchars($invoice['address'])) ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="text-muted mb-1">STATUS:</p>
                    <span class="badge bg-<?= ($invoice['status'] == 'Received') ? 'success' : 'warning' ?>">
                        <?= strtoupper($invoice['status']) ?>
                    </span>
                </div>
            </div>

            </div>
    </div>
</div>
<style>
@media print {
    /* Hide everything except the invoice card */
    .sidebar, .navbar, .no-print, .btn { display: none !important; }
    .content-area { 
        margin-left: 0 !important; 
        width: 100% !important; 
        padding: 0 !important; 
    }
    .card { border: none !important; box-shadow: none !important; }
}
</style>