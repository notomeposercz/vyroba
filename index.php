<?php
require_once 'auth.php';
require_once 'config.php';

// Vyžadovat přihlášení
requireLogin();

date_default_timezone_set('Europe/Prague');
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Výrobní systém - <?php echo date('d.m.Y'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_auth.css">
    <link rel="stylesheet" href="style_calendar.css">
</head>
<body class="calendar-layout role-<?php echo $_SESSION['role']; ?>">
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo">
                    <i class="fas fa-industry"></i>
                </div>
                <h1>Platforma pro přehled výroby</h1>
            </div>
            <div class="header-right">
                <span class="user-info">
                    <i class="fas fa-user"></i> 
                    Přihlášen: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> 
                    <span class="user-role">(<?php echo getRoleDisplayName($_SESSION['role']); ?>)</span>
                </span>
                <span class="date-info">
                    <i class="fas fa-calendar"></i> 
                    <?php echo date('d.m.Y H:i'); ?>
                </span>
                
                <?php if (hasPermission('view_history')): ?>
                <button class="history-btn" onclick="showHistoryModal()">
                    <i class="fas fa-history"></i> Historie
                </button>
                <?php endif; ?>
                
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Odhlásit
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Left Sidebar - Pending Orders -->
        <aside class="left-panel">
            <div class="panel-header">
                <h2><i class="fas fa-shopping-cart"></i> Čekající objednávky</h2>
            </div>
            
            <!-- Filters -->
            <div class="panel-filters">
                <input type="text" id="orderSearchInput" placeholder="Filtrovat produkt/kód..." 
                       class="filter-input">
                <div class="filter-row">
                    <input type="date" id="orderDateFilter" class="filter-input" 
                           title="Filtrovat podle data vytvoření">
                    <select id="orderStatusFilter" class="filter-input">
                        <option value="all">Všechny stavy</option>
                        <option value="Čekající">Čekající</option>
                        <option value="V_výrobě">V výrobě</option>
                        <option value="Hotovo">Hotovo</option>
                    </select>
                </div>
            </div>
            
            <!-- Orders List -->
            <div id="pendingOrdersList" class="orders-list">
                <div class="loading">Načítání objednávek...</div>
            </div>
            
            <!-- Add Order Button -->
            <?php if (hasPermission('edit_orders')): ?>
            <div class="panel-footer">
                <button class="btn btn-primary btn-full" onclick="showAddOrderModal()">
                    <i class="fas fa-plus"></i> Nová objednávka
                </button>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Main Calendar Section -->
        <section class="center-panel">
            <div class="panel-header">
                <h2><i class="fas fa-calendar-alt"></i> Plán Výroby</h2>
                <div class="technology-filter">
                    <span class="filter-label">Filtr:</span>
                    <button class="filter-btn active" data-filter="all">Vše</button>
                    <button class="filter-btn" data-filter="Sítotisk">Sítotisk</button>
                    <button class="filter-btn" data-filter="Potisk">Potisk</button>
                    <button class="filter-btn" data-filter="Gravírování">Gravír</button>
                    <button class="filter-btn" data-filter="Výšivka">Výšivka</button>
                    <button class="filter-btn" data-filter="Laser">Laser</button>
                </div>
            </div>

            <!-- Week Navigation -->
            <div class="week-navigation">
                <button class="nav-btn" onclick="navigateWeek(-1)">
                    <i class="fas fa-chevron-left"></i> Předchozí
                </button>
                <span class="week-display" id="weekDisplay">Načítání...</span>
                <button class="nav-btn" onclick="navigateWeek(1)">
                    Další <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div id="calendarGrid" class="calendar-grid">
                <div class="loading">Načítání kalendáře...</div>
            </div>
            
            <!-- Calendar Actions -->
            <?php if (hasPermission('edit_schedule')): ?>
            <div class="panel-footer">
                <button class="btn btn-primary" onclick="showBlockModal()">
                    <i class="fas fa-plus"></i> Vložit dovolenou/blokaci
                </button>
            </div>
            <?php endif; ?>
        </section>

        <!-- Right Sidebar - Order Details -->
        <aside class="right-panel">
            <div class="panel-header">
                <h2><i class="fas fa-info-circle"></i> Detail Zakázky</h2>
            </div>
            <div id="orderDetails" class="order-details">
                <div class="no-selection">
                    <i class="fas fa-mouse-pointer"></i>
                    <p>Klikněte na objednávku pro zobrazení detailů</p>
                </div>
            </div>
        </aside>
    </main>

    <!-- Footer - Completed Orders -->
    <footer class="main-footer">
        <h2><i class="fas fa-check-circle"></i> Hotové Zakázky</h2>
        <div class="completed-orders-table">
            <table id="completedOrdersTable">
                <thead>
                    <tr>
                        <th>Kód obj.</th>
                        <th>Dokončeno</th>
                        <th>Obchodník</th>
                        <th>Technologie</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="loading">Načítání...</td></tr>
                </tbody>
            </table>
        </div>
    </footer>

    <!-- Modaly -->
    <?php include 'modals.php'; ?>

    <script>
        // Globální proměnné pro oprávnění
        const userPermissions = {
            canEditOrders: <?php echo hasPermission('edit_orders') ? 'true' : 'false'; ?>,
            canEditSchedule: <?php echo hasPermission('edit_schedule') ? 'true' : 'false'; ?>,
            canEditPreview: <?php echo hasPermission('edit_preview_status') ? 'true' : 'false'; ?>,
            canViewHistory: <?php echo hasPermission('view_history') ? 'true' : 'false'; ?>
        };
        
        const userRole = '<?php echo $_SESSION['role']; ?>';
        
        function logout() {
            document.getElementById('logoutModal').style.display = 'block';
        }
        
        function confirmLogout() {
            window.location.href = 'logout.php';
        }
        
        function showHistoryModal() {
            if (userPermissions.canViewHistory) {
                document.getElementById('historyModal').style.display = 'block';
                loadHistory();
            }
        }
    </script>
    <script src="script.js"></script>
</body>
</html>