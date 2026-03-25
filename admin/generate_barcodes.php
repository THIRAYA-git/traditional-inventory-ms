<?php
include('../includes/header.php'); 
include('../includes/sidebar.php');
$pdo = connectDB();

// Fetch products that have barcodes
$products = $pdo->query("SELECT name, SKU, barcode FROM products WHERE barcode IS NOT NULL AND barcode != ''")->fetchAll();
?>

<div class="content-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2 class="h3 page-title">Barcode Label Generator</h2>
            <div>
                <a href="assign_barcodes.php" class="btn btn-success mr-2">
                    <i class="fas fa-magic"></i> Auto-Generate Missing
                </a>
                <button onclick="downloadPDF()" class="btn btn-info mr-2">
            <i class="fas fa-file-pdf"></i> Download PDF
        </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Labels
                </button>
            </div>
        </div>

        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $p): ?>
                    <div class="col-3 mb-4">
                        <div class="barcode-card card text-center shadow-sm p-3 h-100">
                            <h6 class="mb-1 text-truncate card-title" title="<?php echo htmlspecialchars($p['name']); ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </h6>
                            <small class="d-block mb-2 card-sku">
                                SKU: <?php echo !empty($p['SKU']) ? htmlspecialchars($p['SKU']) : 'N/A'; ?>
                            </small>
                             
                            <div class="barcode-wrapper py-2">
                                <img src="https://bwipjs-api.metafloor.com/?bcid=ean13&text=<?php echo urlencode($p['barcode']); ?>&scale=2&height=10&includetext" 
                                     alt="Barcode" class="img-fluid">
                            </div>
                            
                            <div class="mt-2 small fw-bold barcode-number">
                                <?php echo htmlspecialchars($p['barcode']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center no-print">
                    <div class="alert alert-info">No barcodes found. Click "Auto-Generate" to create them.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    :root {
        --bc-bg: #f1f5f9;
        --bc-card: #ffffff;
        --bc-card-border: #e2e8f0;
        --bc-text: #1e293b;
        --bc-text-muted: #64748b;
        --bc-barcode-text: #374151;
    }
    [data-theme="dark"] {
        --bc-bg: #0f172a;
        --bc-card: rgba(30, 41, 59, 0.85);
        --bc-card-border: rgba(255, 255, 255, 0.12);
        --bc-text: #ffffff;
        --bc-text-muted: #94a3b8;
        --bc-barcode-text: #e2e8f0;
    }

    .content-area {
        background: var(--bc-bg);
        transition: background 0.3s ease;
    }

    .page-title { color: var(--bc-text); }

    .barcode-card {
        background: var(--bc-card);
        border: 1px solid var(--bc-card-border);
        transition: background 0.3s ease, border-color 0.3s ease;
    }
    [data-theme="dark"] .barcode-card {
        backdrop-filter: blur(12px);
    }

    .barcode-card .card-title {
        color: var(--bc-text);
    }
    .barcode-card .card-sku {
        color: var(--bc-text-muted);
    }
    .barcode-card .barcode-number {
        color: var(--bc-barcode-text);
    }

    .barcode-wrapper {
        background: #ffffff;
        padding: 8px;
        border-radius: 4px;
        display: inline-block;
    }
    [data-theme="dark"] .barcode-wrapper {
        background: #ffffff;
    }

/* Optimization for standard A4 Paper printing */
@media print {
    /* Hide UI elements */
    .sidebar, .navbar, .no-print, .open-sidebar-button, .footer {
        display: none !important;
    }
    
    /* Expand content area to full width */
    .content-area {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        position: absolute;
        left: 0;
        top: 0;
    }

    /* Force 4 columns per row on paper */
    .row {
        display: flex !important;
        flex-wrap: wrap !important;
    }
    .col-3 {
        flex: 0 0 25% !important;
        max-width: 25% !important;
        padding: 10px !important;
    }

    /* Professional Card Borders for cutting */
    .barcode-card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
        background: #ffffff !important;
    }
}
</style>

<?php include('../includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function downloadPDF() {
    // Select the area containing the labels
    const element = document.querySelector(".row");
    
    // PDF Configuration
    const opt = {
        margin:       0.5,
        filename:     'Barcode-Labels.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, logging: false, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    // Run the conversion
    html2pdf().set(opt).from(element).save();
}
</script>

