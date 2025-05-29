<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Náhled Platformy Výroby</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/inter-ui/3.19.3/inter.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --primary-dark: #3730a3;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-100);
        }
        
        /* Custom styles */
        .calendar-day { 
            min-height: 180px;
            position: relative;
            border-radius: 0.5rem;
        }
        
        .order-card { 
            border-left-width: 4px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .status-green { border-left-color: var(--success); }
        .status-orange { border-left-color: var(--warning); }
        .status-red { border-left-color: var(--danger); }
        
        .blinking { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.3; } }
        
        .panel-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--gray-400) var(--gray-200);
        }
        
        .panel-scroll::-webkit-scrollbar { width: 6px; }
        .panel-scroll::-webkit-scrollbar-track { background: var(--gray-200); border-radius: 3px; }
        .panel-scroll::-webkit-scrollbar-thumb { background-color: var(--gray-400); border-radius: 3px; border: 1px solid var(--gray-200); }
        
        /* Calendar Styling */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0; /* Remove gap between days */
        }
        
        .calendar-order-block {
            position: relative;
            margin: 4px 0;
            min-height: 50px;
            transition: transform 0.15s, box-shadow 0.15s;
            overflow: hidden;
            z-index: 5;
        }
        
        .calendar-order-block:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* Multi-day events styling */
        .multi-day-order {
            border-width: 1px;
            position: relative;
        }
        
        /* First day of multi-day event */
        .multi-day-start {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            margin-right: -1px; /* Overlap with next day */
            z-index: 10;
        }
        
        /* Middle day of multi-day event */
        .multi-day-middle {
            border-radius: 0 !important;
            margin-left: -1px; /* Connect with previous day */
            margin-right: -1px; /* Connect with next day */
            z-index: 9;
        }
        
        /* Last day of multi-day event */
        .multi-day-end {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            margin-left: -1px; /* Connect with previous day */
            z-index: 10;
        }
        
        /* Add visual continuity across days */
        .multi-day-join {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 10px;
            z-index: 8;
        }
        
        .join-left {
            left: -5px;
        }
        
        .join-right {
            right: -5px;
        }
        
        /* Day styling */
        .day-header {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            border-bottom: 1px solid var(--gray-200);
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            padding: 8px;
            font-weight: 600;
        }
        
        /* Filter button styling */
        .filter-button {
            padding: 4px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        
        .filter-button.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .filter-button:not(.active):hover {
             background-color: var(--gray-100);
        }
        
        /* Modern buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0d9668;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .btn-gray {
            background-color: var(--gray-500);
            color: white;
        }
        
        .btn-gray:hover {
            background-color: var(--gray-600);
        }
        
        /* Calendar day styles with subtle borders */
        .calendar-border {
            border: 1px solid var(--gray-200);
        }
        
        .holiday {
            background-color: #f1f5f9;
            color: var(--gray-500);
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }
        .hidden { display: none; }
    </style>
</head>
<body class="flex flex-col h-screen">

    <header class="bg-white shadow-sm p-4 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center space-x-4">
            <div class="text-xl font-bold text-primary" style="color: var(--primary);">[Logo]</div>
            <h1 class="text-lg font-semibold text-gray-700">Platforma pro přehled výroby</h1>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">Přihlášen: Jan Novák (Výroba)</span>
            <button class="text-sm hover:underline" style="color: var(--primary);">Odhlásit</button>
        </div>
    </header>

    <main class="flex flex-1 overflow-hidden p-4 gap-4">

        <!-- Left Sidebar - Pending Orders -->
        <aside class="w-1/4 bg-white rounded-lg shadow-sm p-4 flex flex-col panel-scroll">
            <h2 class="text-lg font-semibold mb-3 text-gray-800 border-b pb-2">Čekající objednávky</h2>
            <div class="mb-3 flex space-x-2">
                <input type="text" placeholder="Filtrovat produkt/kód..." class="flex-grow p-2 border rounded text-sm">
                <input type="date" class="p-2 border rounded text-sm" title="Filtrovat podle data vytvoření">
            </div>
            <div class="space-y-3 flex-1">
                <!-- Order Card 1 -->
                <div class="p-3 border rounded-md shadow-sm order-card status-green hover:shadow-md">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-sm">OBJ2025-0123</span>
                        <span class="text-xs text-gray-500">Vytvořeno: 01.04.2025</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">150 ks / Sítotisk</p>
                    <div class="text-xs mb-2">
                        <span>Zboží: <span class="font-medium" style="color: var(--success);">Na skladě</span></span>
                        <span class="text-gray-500 text-[10px] ml-1">(15.04.2025)</span>
                    </div>
                    <div class="text-xs mb-2 border-t pt-2">
                        <div class="flex justify-between items-center">
                            <span class="preview-status" data-status="Čeká">Náhled: <span class="font-medium" style="color: var(--warning);">Čeká</span></span>
                            <div>
                                <button class="text-[10px] hover:underline" style="color: var(--success);" title="Schválit náhled" data-action="approve">(S)</button>
                                <button class="ml-1 text-[10px] hover:underline" style="color: var(--danger);" title="Zamítnout náhled" data-action="reject">(Z)</button>
                            </div>
                        </div>
                        <p class="approval-date text-gray-500 text-[10px] mt-0.5 hidden">Schváleno: <span class="date-value">DD.MM.RRRR</span></p>
                    </div>
                     <div class="shipping-date-section text-xs border-t pt-2">
                        <label class="block text-gray-600 mb-0.5">Datum odeslání:</label>
                        <input type="date" class="p-1 border rounded text-sm w-full" value="2025-05-05">
                    </div>
                </div>
                
                <!-- Order Card 2 -->
                <div class="p-3 border rounded-md shadow-sm order-card status-orange hover:shadow-md">
                     <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-sm">OBJ2025-0124</span>
                        <span class="text-xs text-gray-500">Vytvořeno: 02.04.2025</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">50 ks / Potisk</p>
                    <div class="text-xs mb-2">
                        <span>Zboží: <span class="font-medium" style="color: var(--warning);">Objednáno</span></span>
                    </div>
                    <div class="text-xs mb-2 border-t pt-2">
                         <div class="flex justify-between items-center">
                            <span class="preview-status" data-status="Schváleno">Náhled: <span class="font-medium" style="color: var(--success);">Schváleno</span></span>
                            <div>
                                 <button class="ml-1 text-[10px] hover:underline" style="color: var(--warning);" title="Vrátit na čeká" data-action="revert">(Č)</button>
                                <button class="ml-1 text-[10px] hover:underline" style="color: var(--danger);" title="Zamítnout náhled" data-action="reject">(Z)</button>
                            </div>
                        </div>
                         <p class="approval-date text-gray-500 text-[10px] mt-0.5">Schváleno: <span class="date-value">29.04.2025</span></p>
                    </div>
                     <div class="shipping-date-section text-xs border-t pt-2">
                        <label class="block text-gray-600 mb-0.5">Datum odeslání:</label>
                        <input type="date" class="p-1 border rounded text-sm w-full" value="2025-05-15">
                    </div>
                </div>
                
                <!-- Order Card 3 -->
                <div class="p-3 border rounded-md shadow-sm order-card status-red hover:shadow-md">
                     <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-sm">OBJ2025-0125</span>
                         <span class="text-xs text-gray-500">Vytvořeno: 03.04.2025</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">200 ks / Gravírování</p>
                     <div class="text-xs mb-2">
                        <span>Zboží: <span class="font-medium" style="color: var(--danger);">Není</span></span>
                    </div>
                     <div class="text-xs mb-2 border-t pt-2">
                         <div class="flex justify-between items-center">
                            <span class="preview-status" data-status="Čeká">Náhled: <span class="font-medium" style="color: var(--warning);">Čeká</span></span>
                             <div>
                                <button class="text-[10px] hover:underline" style="color: var(--success);" title="Schválit náhled" data-action="approve">(S)</button>
                                <button class="ml-1 text-[10px] hover:underline" style="color: var(--danger);" title="Zamítnout náhled" data-action="reject">(Z)</button>
                            </div>
                        </div>
                         <p class="approval-date text-gray-500 text-[10px] mt-0.5 hidden">Schváleno: <span class="date-value">DD.MM.RRRR</span></p>
                    </div>
                     <div class="shipping-date-section text-xs border-t pt-2">
                        <label class="block text-gray-600 mb-0.5">Datum odeslání:</label>
                        <input type="date" class="p-1 border rounded text-sm w-full" value="2025-05-12">
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Calendar Section -->
        <section class="flex-1 bg-white rounded-lg shadow-sm p-4 flex flex-col">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Plán Výroby</h2>
                <div class="flex items-center space-x-2" id="technology-filter">
                    <span class="text-xs font-medium text-gray-500">Filtr:</span>
                    <button class="filter-button active" data-filter="all">Vše</button>
                    <button class="filter-button" data-filter="Sítotisk">Sítotisk</button>
                    <button class="filter-button" data-filter="Potisk">Potisk</button>
                    <button class="filter-button" data-filter="Gravírování">Gravír</button>
                    <button class="filter-button" data-filter="Výšivka">Výšivka</button>
                    <button class="filter-button" data-filter="Laser">Laser</button>
                </div>
            </div>

            <div class="flex justify-between items-center mb-4">
                <button class="btn px-3 py-1" style="background-color: var(--gray-200);">&lt;&lt; Předchozí</button>
                <span class="font-semibold">Týden (28.4. - 2.5. 2025)</span>
                <button class="btn px-3 py-1" style="background-color: var(--gray-200);">Další &gt;&gt;</button>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid flex-1">
                <!-- Monday -->
                <div class="calendar-day mr-px calendar-border">
                    <div class="day-header text-center">Pondělí (28.4.)</div>
                    <div class="p-2 space-y-1 flex-1 overflow-y-auto">
                        <!-- Potisk Order (Multi-day start) -->
                        <div class="calendar-order-block rounded p-1 text-xs cursor-move multi-day-order multi-day-start" 
                             style="background-color: #dbeafe; border-color: #60a5fa; color: #1e40af;"
                             data-technology="Potisk" data-order-id="OBJ2025-0124"
                             title="OBJ2025-0124 (Potisk) | Start: 28.4., Konec: 15.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0124</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">50 ks - Potisk</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-right" style="background-color: #60a5fa;"></div>
                        </div>
                        
                        <!-- Výšivka Order -->
                        <div class="calendar-order-block rounded p-1 text-xs cursor-move blinking" 
                             style="background-color: #fef9c3; border-color: #facc15; color: #854d0e;"
                             data-technology="Výšivka" 
                             title="OBJ2025-0126 (Výšivka) | Konec: 29.4.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0126</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">300 ks - Výšivka</div>
                            <button class="btn-success text-white text-[10px] py-0.5 px-1 rounded w-full mt-1">HOTOVO</button>
                        </div>
                    </div>
                </div>
                
                <!-- Tuesday -->
                <div class="calendar-day mx-px calendar-border">
                    <div class="day-header text-center">Úterý (29.4.)</div>
                    <div class="p-2 space-y-1 flex-1 overflow-y-auto">
                        <!-- Potisk Order (Multi-day middle) -->
                        <div class="calendar-order-block p-1 text-xs cursor-move multi-day-order multi-day-middle" 
                             style="background-color: #dbeafe; border-color: #60a5fa; color: #1e40af;"
                             data-technology="Potisk" data-order-id="OBJ2025-0124"
                             title="OBJ2025-0124 (Potisk) | Start: 28.4., Konec: 15.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0124</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">(pokrač.)</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-left" style="background-color: #60a5fa;"></div>
                            <div class="multi-day-join join-right" style="background-color: #60a5fa;"></div>
                        </div>
                        
                        <!-- Holiday block -->
                        <div class="bg-gray-200 p-1.5 rounded text-xs border border-gray-300 text-center italic">DOVOLENÁ</div>
                    </div>
                </div>
                
                <!-- Wednesday -->
                <div class="calendar-day mx-px calendar-border">
                    <div class="day-header text-center">Středa (30.4.)</div>
                    <div class="p-2 space-y-1 flex-1 overflow-y-auto">
                        <!-- Potisk Order (Multi-day middle) -->
                        <div class="calendar-order-block p-1 text-xs cursor-move multi-day-order multi-day-middle" 
                             style="background-color: #dbeafe; border-color: #60a5fa; color: #1e40af;"
                             data-technology="Potisk" data-order-id="OBJ2025-0124"
                             title="OBJ2025-0124 (Potisk) | Start: 28.4., Konec: 15.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0124</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">(pokrač.)</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-left" style="background-color: #60a5fa;"></div>
                            <div class="multi-day-join join-right" style="background-color: #60a5fa;"></div>
                        </div>
                        
                        <!-- Laser Order -->
                        <div class="calendar-order-block rounded p-1 text-xs cursor-move" 
                             style="background-color: #e0f2fe; border: 2px solid #0ea5e9; color: #0c4a6e;"
                             data-technology="Laser" 
                             title="OBJ2025-0127 (Laser) | Konec: 30.4.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0127 (🔒)</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">100 ks - Laser</div>
                            <button class="btn-success text-white text-[10px] py-0.5 px-1 rounded w-full mt-1">HOTOVO</button>
                        </div>
                        
                        <!-- Second Potisk Order (Multi-day start) -->
                        <div class="calendar-order-block rounded-l p-1 text-xs cursor-move multi-day-order multi-day-start" 
                             style="background-color: #f3e8ff; border-color: #a78bfa; color: #5b21b6;"
                             data-technology="Potisk" data-order-id="OBJ2025-0128"
                             title="OBJ2025-0128 (Potisk) | Start: 30.4., Konec: 10.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0128</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">500 ks - Potisk</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-right" style="background-color: #a78bfa;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Thursday (Holiday) -->
                <div class="calendar-day mx-px holiday">
                    <div class="day-header text-center">Čtvrtek (1.5.)</div>
                    <div class="flex items-center justify-center h-full">
                        <div class="text-xs text-center text-gray-500 font-medium">SVÁTEK</div>
                    </div>
                </div>
                
                <!-- Friday -->
                <div class="calendar-day ml-px calendar-border">
                    <div class="day-header text-center">Pátek (2.5.)</div>
                    <div class="p-2 space-y-1 flex-1 overflow-y-auto">
                        <!-- Potisk Order (Multi-day middle) -->
                        <div class="calendar-order-block p-1 text-xs cursor-move multi-day-order multi-day-middle" 
                             style="background-color: #dbeafe; border-color: #60a5fa; color: #1e40af;"
                             data-technology="Potisk" data-order-id="OBJ2025-0124"
                             title="OBJ2025-0124 (Potisk) | Start: 28.4., Konec: 15.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0124</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">(pokrač.)</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-left" style="background-color: #60a5fa;"></div>
                        </div>
                        
                        <!-- Second Potisk Order (Multi-day middle) -->
                        <div class="calendar-order-block p-1 text-xs cursor-move multi-day-order multi-day-middle" 
                             style="background-color: #f3e8ff; border-color: #a78bfa; color: #5b21b6;"
                             data-technology="Potisk" data-order-id="OBJ2025-0128"
                             title="OBJ2025-0128 (Potisk) | Start: 30.4., Konec: 10.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0128</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">(pokrač.)</div>
                            <button class="invisible bg-green-500 text-xs mt-1 w-full">HOTOVO</button>
                            <div class="multi-day-join join-left" style="background-color: #a78bfa;"></div>
                        </div>
                        
                        <!-- Sitotisk Order (Multi-day end) -->
                        <div class="calendar-order-block rounded-r p-1 text-xs cursor-move multi-day-order multi-day-end" 
                             style="background-color: #ccfbf1; border-color: #2dd4bf; color: #115e59;"
                             data-technology="Sítotisk" data-order-id="OBJ2025-0123"
                             title="OBJ2025-0123 (Sítotisk) | Start: ?, Konec: 2.5.">
                            <div class="text-center font-semibold text-[10px] truncate">OBJ2025-0123</div>
                            <div class="text-center text-[9px] text-gray-700 my-0.5 truncate">150 ks - Sítotisk</div>
                            <button class="btn-success text-white text-[10px] py-0.5 px-1 rounded w-full mt-1">HOTOVO</button>
                            <div class="multi-day-join join-left" style="background-color: #2dd4bf;"></div>
                        </div>
                        
                        <!-- Maintenance block -->
                        <div class="bg-gray-200 p-1.5 rounded text-xs border border-gray-300 text-center italic">BLOKACE (Údržba)</div>
                    </div>
                </div>
            </div>
            
            <!-- Calendar Actions -->
            <div class="mt-4 text-right">
                <button class="btn btn-primary">Vložit dovolenou/blokaci</button>
            </div>
        </section>

        <!-- Right Sidebar - Order Details -->
        <aside class="w-1/4 bg-white rounded-lg shadow-sm p-4 flex flex-col panel-scroll">
             <h2 class="text-lg font-semibold mb-3 text-gray-800 border-b pb-2">Detail Zakázky</h2>
             <div class="space-y-3 text-sm">
                 <div><span class="font-semibold text-gray-600 w-28 inline-block">Kód obj.:</span> OBJ2025-0124</div>
                 <div><span class="font-semibold text-gray-600 w-28 inline-block">Vytvořeno:</span> 02.04.2025</div>
                 <div><span class="font-semibold text-gray-600 w-28 inline-block">Požad. expedice:</span> 15.05.2025</div>
                 <div><span class="font-semibold text-gray-600 w-28 inline-block">Obchodník:</span> Jan Novák</div>
                 <hr>
                 <div>
                     <span class="font-semibold text-gray-600 block mb-1">Položky:</span>
                     <ul class="list-disc list-inside ml-2 text-xs space-y-1">
                         <li>Hrnek bílý 300ml, 50 ks, Potisk</li>
                     </ul>
                 </div>
                 <hr>
                  <div><span class="font-semibold text-gray-600 w-28 inline-block">Stav náhledu:</span> <span class="font-medium" style="color: var(--success);">Schváleno</span> (29.04.2025)</div>
                  <div><span class="font-semibold text-gray-600 w-28 inline-block">Stav zboží:</span> <span class="font-medium" style="color: var(--warning);">Objednáno</span></div>
                 <hr>
                 <div>
                     <span class="font-semibold text-gray-600 block mb-1">Interní poznámky:</span>
                     <div class="space-y-1 text-xs bg-gray-50 p-2 rounded border max-h-32 overflow-y-auto">
                         <p><span class="text-gray-500">[29.04. 11:00 Novák]:</span> Schválen náhled.</p>
                     </div>
                     <button class="mt-2 text-xs hover:underline" style="color: var(--primary);">+ Přidat poznámku</button>
                 </div>
                 <hr>
                 <div class="space-x-2 pt-2 text-right">
                     <button class="btn btn-success text-xs">Označit jako hotovo</button>
                     <button class="btn btn-warning text-xs">Uzamknout termín</button>
                     <button class="btn btn-gray text-xs">Vrátit do plánu</button>
                 </div>
             </div>
        </aside>
    </main>

    <footer class="bg-white rounded-lg shadow-sm p-3 mx-4 mb-4 flex-shrink-0 overflow-x-auto">
         <h2 class="text-md font-semibold mb-2 text-gray-700">Hotové Zakázky</h2>
         <table class="min-w-full text-xs">
             <thead>
                 <tr class="text-left text-gray-600 bg-gray-50">
                     <th class="py-2 px-3 border-b">Kód obj.</th>
                     <th class="py-2 px-3 border-b">Dokončeno</th>
                     <th class="py-2 px-3 border-b">Obchodník</th>
                     <th class="py-2 px-3 border-b">Akce</th>
                 </tr>
             </thead>
             <tbody>
                 <tr class="hover:bg-gray-50">
                     <td class="py-2 px-3 border-b">OBJ2025-0099</td>
                     <td class="py-2 px-3 border-b">25.4.2025 15:30</td>
                     <td class="py-2 px-3 border-b">Novák</td>
                     <td class="py-2 px-3 border-b"><button class="text-blue-500 hover:underline">[✉️ Odeslat]</button></td>
                 </tr>
                  <tr class="hover:bg-gray-50">
                     <td class="py-2 px-3 border-b">OBJ2025-0101</td>
                     <td class="py-2 px-3 border-b">26.4.2025 10:00</td>
                     <td class="py-2 px-3 border-b">Svoboda</td>
                     <td class="py-2 px-3 border-b text-gray-400">(Odesláno)</td>
                 </tr>
             </tbody>
         </table>
    </footer>

    <script>
        console.log("Modernizovaná platforma načtena.");

        // --- Left Panel Logic ---
        document.querySelectorAll('.order-card').forEach(card => {
            const approveButton = card.querySelector('button[data-action="approve"]');
            const revertButton = card.querySelector('button[data-action="revert"]');
            const rejectButton = card.querySelector('button[data-action="reject"]');
            const previewStatusSpan = card.querySelector('.preview-status span');
            const previewStatusContainer = card.querySelector('.preview-status');
            const approvalDateElement = card.querySelector('.approval-date');
            const approvalDateValue = approvalDateElement?.querySelector('.date-value');

            function formatDate(date) {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}.${month}.${year}`;
            }

            if (approveButton) {
                approveButton.addEventListener('click', (event) => {
                    const orderCode = card.querySelector('.font-semibold').textContent;
                    previewStatusContainer.dataset.status = 'Schváleno';
                    previewStatusSpan.textContent = 'Schváleno';
                    previewStatusSpan.style.color = 'var(--success)';
                    if (approvalDateElement && approvalDateValue) {
                        approvalDateValue.textContent = formatDate(new Date());
                        approvalDateElement.classList.remove('hidden');
                    }
                    alert(`Náhled pro ${orderCode} schválen.`);
                });
            }

            const revertOrRejectAction = (event) => {
                const orderCode = card.querySelector('.font-semibold').textContent;
                const action = event.target.dataset.action;
                previewStatusContainer.dataset.status = 'Čeká';
                previewStatusSpan.textContent = 'Čeká';
                previewStatusSpan.style.color = 'var(--warning)';
                if (approvalDateElement) {
                    approvalDateElement.classList.add('hidden');
                }
                alert(`Náhled pro ${orderCode} ${action === 'revert' ? 'vrácen na čekání' : 'zamítnut'}.`);
            };

            if (revertButton) revertButton.addEventListener('click', revertOrRejectAction);
            if (rejectButton) rejectButton.addEventListener('click', revertOrRejectAction);

            if (previewStatusContainer.dataset.status === 'Schváleno') {
                if (approvalDateElement) approvalDateElement.classList.remove('hidden');
            } else {
                if (approvalDateElement) approvalDateElement.classList.add('hidden');
            }
        });

        // --- Calendar Panel Logic ---
        const filterContainer = document.getElementById('technology-filter');
        const calendarDays = document.querySelectorAll('.calendar-day');
        
        // Function to highlight multi-day order blocks that belong together
        function highlightRelatedBlocks(orderId, highlight) {
            document.querySelectorAll(`.calendar-order-block[data-order-id="${orderId}"]`).forEach(block => {
                if (highlight) {
                    block.style.boxShadow = '0 0 0 2px var(--primary)';
                    block.style.zIndex = '20';
                } else {
                    block.style.boxShadow = '';
                    block.style.zIndex = '';
                }
            });
        }

        // Add hover effects to multi-day orders
        document.querySelectorAll('.multi-day-order').forEach(block => {
            const orderId = block.dataset.orderId;
            if (orderId) {
                block.addEventListener('mouseenter', () => highlightRelatedBlocks(orderId, true));
                block.addEventListener('mouseleave', () => highlightRelatedBlocks(orderId, false));
            }
        });

        filterContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('filter-button')) {
                filterContainer.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
                const filterValue = event.target.dataset.filter;

                // Iterate over each day column
                calendarDays.forEach(day => {
                    // Find all order blocks within this day
                    const orderBlocks = day.querySelectorAll('.calendar-order-block');
                    orderBlocks.forEach(block => {
                        const blockTechnology = block.dataset.technology;
                        // Hide/show based on filter
                        if (filterValue === 'all' || !blockTechnology || blockTechnology === filterValue) {
                            block.classList.remove('hidden');
                        } else {
                            block.classList.add('hidden');
                        }
                    });
                });
            }
        });

        // Event listeners for "HOTOVO" buttons in calendar
        document.querySelectorAll('.calendar-order-block button').forEach(button => {
            if (!button.classList.contains('invisible')) {
                button.addEventListener('click', (event) => {
                    event.stopPropagation(); // Prevent potential drag/drop triggers
                    const orderBlock = event.target.closest('.calendar-order-block');
                    const orderCodeElement = orderBlock.querySelector('.font-semibold');
                    if (orderCodeElement) {
                        const orderCode = orderCodeElement.textContent.split(' ')[0];
                        console.log(`Tlačítko HOTOVO kliknuto pro zakázku: ${orderCode}`);
                        alert(`Označit zakázku ${orderCode} jako hotovou?`);
                    } else {
                        console.error("Could not find order code element in calendar block.");
                    }
                });
            }
        });
    </script>
</body>
</html>