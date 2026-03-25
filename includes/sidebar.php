<?php

/**
 * File: inventory-ms/includes/sidebar.php
 * Fully corrected version with responsive full-width and hierarchy styling.
 */
?>

<style>
    /* ======================================================
   1. CORE VARIABLES & SIDEBAR BASE
   ====================================================== */
    :root {
        --sidebar-width: 250px;
        --sidebar-bg: #212529;
        --sidebar-active: #05db73e1;
        --submenu-bg: #1a1d21;
        --topbar-height: 56px;
    }

    .sidebar {
        position: fixed;
        top: var(--topbar-height);
        left: 0;
        width: var(--sidebar-width);
        height: calc(100vh - var(--topbar-height));
        background: var(--sidebar-bg);
        color: #fff;
        overflow-y: auto;
        z-index: 1020;
        transition: transform 0.3s ease-in-out;
    }

    .menu-list {
        padding: 15px 0;
    }

    .components {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    /* ======================================================
   2. MAIN MENU ITEMS (Bigger Font - 17px)
   ====================================================== */
    .components li a,
    .components li .dropdown-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        color: #dcdcdc;
        text-decoration: none;
        font-size: 17px !important;
        /* Increased visibility */
        font-weight: 500;
        transition: background 0.2s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
    }

    .components li a:hover,
    .dropdown-btn:hover {
        background: #2c3136;
        color: #fff;
    }

    .components li.active>a {
        background: var(--sidebar-active);
        color: #fff;
    }

    /* ======================================================
   3. SUBMENU ITEMS (Font: 14px | Indent: 60px)
   ====================================================== */
    .dropdown-container {
        display: none;
        background: var(--submenu-bg);
        list-style: none;
        padding: 5px 0;
    }

    .components .dropdown-container li a {
        padding-left: 60px !important;
        /* Clear visual hierarchy */
        font-size: 14px !important;
        /* Slightly smaller than main items */
        color: #b8bcbf !important;
        font-weight: 400 !important;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .components .dropdown-container li a:hover {
        color: #fff !important;
        background: #262a2e !important;
    }

    .components .dropdown-container li.active a {
        background: var(--sidebar-active) !important;
        color: #fff !important;
    }

    .chevron {
        margin-left: auto;
        transition: transform 0.3s;
        font-size: 12px;
    }

    .dropdown-btn.active .chevron {
        transform: rotate(180deg);
    }

    /* ======================================================
   4. MOBILE RESPONSIVE & FULL-WIDTH FIXES
   ====================================================== */
    #sidebar-toggle {
        display: none;
    }

    .open-sidebar-btn,
    .close-sidebar-btn {
        display: none;
        cursor: pointer;
    }

    @media (max-width: 992px) {

        /* Hide Logo on Mobile as requested */
        #hideLogo,
        .navbar-brand {
            display: none !important;
        }

        /* Force pages to take FULL WIDTH on mobile */
        .container,
        .container-fluid,
        main,
        .content-wrapper,
        #main-content {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 10px !important;
            padding-right: 10px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        /* Sidebar full width when opened */
        .sidebar {
            transform: translateX(-100%);
            width: 100%;
            top: 0;
            height: 100vh;
        }

        #sidebar-toggle:checked~.sidebar {
            transform: translateX(0);
        }

        .open-sidebar-btn {
            display: inline-flex;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1035;
            background: #2c3e50;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
        }

        .close-sidebar-btn {
            display: inline-flex;
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #fff;
        }
    }
</style>

<input type="checkbox" id="sidebar-toggle">
<label for="sidebar-toggle" class="open-sidebar-btn"><i class="fas fa-bars"></i></label>

<nav class="sidebar">
    <label for="sidebar-toggle" class="close-sidebar-btn"><i class="fas fa-times"></i></label>

    <div class="menu-list">
        <ul class="components">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $current_dir  = basename(dirname($_SERVER['PHP_SELF']));

            if ($is_admin): ?>

                <li class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="<?= $root_path ?>admin/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                </li>

                <?php $warehouse_pages = ['transfer_stock.php', 'warehouse_report.php', 'stock_log.php', 'stock_adjustment.php', 'warehouses.php']; ?>
                <li>
                    <button class="dropdown-btn <?= in_array($current_page, $warehouse_pages) ? 'active' : '' ?>">
                        <i class="fas fa-warehouse"></i> Warehouse
                        <i class="fas fa-chevron-down chevron"></i>
                    </button>
                    <ul class="dropdown-container" <?= in_array($current_page, $warehouse_pages) ? 'style="display: block;"' : '' ?>>
                        <li class="<?= $current_page === 'warehouse_report.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/warehouse_report.php">Stock Report</a></li>
                        <li class="<?= $current_page === 'transfer_stock.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/transfer_stock.php">Transfer Stock</a></li>
                        <li class="<?= $current_page === 'stock_log.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/stock_log.php">Stock Log</a></li>
                        <li class="<?= $current_page === 'stock_adjustment.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/stock_adjustment.php">Edit stock levels</a></li>
                    </ul>
                </li>

                <?php $sales_pages = ['products.php', 'generate_barcodes.php', 'categories.php', 'orders.php']; ?>
                <li>
                    <button class="dropdown-btn <?= (in_array($current_page, $sales_pages) && $current_dir === 'admin') ? 'active' : '' ?>">
                        <i class="fas fa-shopping-cart"></i> Sales
                        <i class="fas fa-chevron-down chevron"></i>
                    </button>
                    <ul class="dropdown-container" <?= in_array($current_page, $sales_pages) ? 'style="display: block;"' : '' ?>>
                        <li class="<?= $current_page === 'products.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/products.php">Products</a></li>
                        <li class="<?= $current_page === 'generate_barcodes.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/generate_barcodes.php">Barcode generator</a></li>
                        <li class="<?= $current_page === 'categories.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/categories.php">Categorize Product</a></li>
                        <li class="<?= $current_page === 'orders.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/orders.php">Sales orders</a></li>
                    </ul>
                </li>

                <?php $purchase_pages = ['purchase_orders.php', 'suppliers.php', 'create_po.php']; ?>
                <li>
                    <button class="dropdown-btn <?= in_array($current_page, $purchase_pages) ? 'active' : '' ?>">
                        <i class="fas fa-shopping-basket"></i> Purchase
                        <i class="fas fa-chevron-down chevron"></i>
                    </button>
                    <ul class="dropdown-container" <?= in_array($current_page, $purchase_pages) ? 'style="display: block;"' : '' ?>>
                        <li class="<?= $current_page === 'create_po.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/create_po.php">New Purchase</a></li>
                        <li class="<?= $current_page === 'purchase_orders.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/purchase_orders.php">Purchase Status</a></li>
                        <li class="<?= $current_page === 'suppliers.php' ? 'active' : '' ?>"><a href="<?= $root_path ?>admin/suppliers.php">Suppliers</a></li>
                    </ul>
                </li>
                <?php
                $invoice_pages = ['sales_invoices_print.php', 'purchase_invoices_print.php'];
                ?>

                <li>
                    <button class="dropdown-btn <?= in_array($current_page, $invoice_pages) ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice"></i> Invoices
                        <i class="fas fa-chevron-down chevron"></i>
                    </button>
                    <ul class="dropdown-container" <?= in_array($current_page, $invoice_pages) ? 'style="display: block;"' : '' ?>>
                        <li class="<?= $current_page === 'sales_invoices_print.php' ? 'active' : '' ?>">
                            <a href="<?= $root_path ?>admin/sales_invoices_print.php">
                                Sales Invoices
                            </a>
                        </li>
                        <li class="<?= $current_page === 'purchase_invoices_print.php' ? 'active' : '' ?>">
                            <a href="<?= $root_path ?>admin/purchase_invoices_print.php">
                                Purchase Invoices
                            </a>
                        </li>
                    </ul>
                </li>


                <li class="<?= $current_page === 'users.php' ? 'active' : '' ?>">
                    <a href="<?= $root_path ?>admin/users.php"><i class="fas fa-users"></i> Users</a>
                </li>

            <?php else: ?>
                <li class="<?= ($current_page === 'products.php' && $current_dir === 'employee') ? 'active' : '' ?>">
                    <a href="<?= $root_path ?>employee/products.php"><i class="fas fa-box"></i> Products</a>
                </li>
                <li class="<?= ($current_page === 'orders.php' && $current_dir === 'employee') ? 'active' : '' ?>">
                    <a href="<?= $root_path ?>employee/orders.php"><i class="fas fa-receipt"></i> Orders</a>
                </li>
            <?php endif; ?>


            <li class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <a href="<?= $root_path . ($is_admin ? 'admin' : 'employee') ?>/profile.php"><i class="fas fa-user-circle"></i> Profile</a>
            </li>

            <li><a href="<?= $root_path ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<script>
    /* Dropdown logic with persistency */
    var dropdown = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdown.length; i++) {
        dropdown[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var dropdownContent = this.nextElementSibling;
            dropdownContent.style.display = (dropdownContent.style.display === "block") ? "none" : "block";
        });
    }

    // Keep dropdown open if a submenu item is active
    var dropdownContainers = document.querySelectorAll('.dropdown-container');
    dropdownContainers.forEach(function(container) {
        var activeItems = container.querySelectorAll('li.active');
        if (activeItems.length > 0) {
            container.style.display = "block";
            var btn = container.previousElementSibling;
            if (btn && btn.classList.contains('dropdown-btn')) {
                btn.classList.add('active');
            }
        }
    });
</script>