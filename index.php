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
    <style>
/* Kalendářní objednávky */
.calendar-order {
    background: #e5e7eb;
    border-left: 4px solid #6b7280;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.calendar-order:hover {
    background: #d1d5db;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tech-sitotisk { border-left-color: #ef4444; }
.tech-potisk { border-left-color: #3b82f6; }
.tech-gravirovani { border-left-color: #8b5cf6; }
.tech-vysivka { border-left-color: #10b981; }
.tech-laser { border-left-color: #f59e0b; }
.tech-default { border-left-color: #6b7280; }

.order-code-cal {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.order-info-cal {
    color: #6b7280;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.order-actions-cal {
    display: flex;
    justify-content: flex-end;
}

.mark-completed-btn {
    background: #10b981;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.mark-completed-btn:hover {
    background: #059669;
}

.no-orders-day {
    color: #9ca3af;
    font-style: italic;
    text-align: center;
    padding: 1rem;
    font-size: 0.8rem;
}

.error-message {
    background: #fef2f2;
    color: #dc2626;
    padding: 1rem;
    border-radius: 6px;
    text-align: center;
    margin: 1rem;
    border: 1px solid #fecaca;
}

/* Import tlačítko styly */
.import-csv-btn {
    background: #8b5cf6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    margin-right: 0.5rem;
    transition: background-color 0.2s;
    font-size: 0.9rem;
}

.import-csv-btn:hover {
    background: #7c3aed;
}

.import-csv-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.import-status {
    padding: 0.75rem;
    border-radius: 6px;
    margin: 1rem 0;
    font-size: 0.9rem;
}

.import-status.success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.import-status.error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.import-status.loading {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
</style>
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
                
                <!-- CSV Import Button - pouze pro admin a obchodníky -->
                <?php if (hasAnyRole(['admin', 'obchodnik'])): ?>
                <button class="import-csv-btn" onclick="importCSV()" id="importBtn">
                    <i class="fas fa-file-csv"></i> Import CSV
                </button>
                <?php endif; ?>
                
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

    <!-- CSV Import Status -->
    <div id="importStatus" class="import-status" style="display: none;"></div>

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
        
        // CSV Import funkce
        async function importCSV() {
            const importBtn = document.getElementById('importBtn');
            const statusDiv = document.getElementById('importStatus');
            
            // Zobrazit načítání
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importuji...';
            
            statusDiv.className = 'import-status loading';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Zpracovávám CSV soubor...';
            statusDiv.style.display = 'block';
            
            try {
                const response = await fetch('import_csv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    statusDiv.className = 'import-status success';
                    statusDiv.innerHTML = `
                        <i class="fas fa-check-circle"></i> 
                        Import úspěšný! Zpracováno ${result.processed_count} objednávek.
                        <br>Použitý soubor: ${result.csv_file_used}
                    `;
                    
                    // Obnovit data na stránce
                    if (typeof loadOrders === 'function') {
                        loadOrders();
                    }
                    if (typeof loadCalendar === 'function') {
                        loadCalendar();
                    }
                    
                } else {
                    statusDiv.className = 'import-status error';
                    statusDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i> 
                        Chyba při importu: ${result.error}
                    `;
                }
                
            } catch (error) {
                statusDiv.className = 'import-status error';
                statusDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i> 
                    Síťová chyba: ${error.message}
                `;
            } finally {
                // Obnovit tlačítko
                importBtn.disabled = false;
                importBtn.innerHTML = '<i class="fas fa-file-csv"></i> Import CSV';
                
                // Skrýt status za 10 sekund
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 10000);
            }
        }
        
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