<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NMI API Integration Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <!-- Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ["Inter", "sans-serif"],
            },
            colors: {
              teal: {
                50: "#f0fdfa",
                100: "#ccfbf1",
                500: "#14b8a6",
                600: "#0d9488",
              },
              purple: {
                50: "#faf5ff",
                100: "#f3e8ff",
                500: "#a855f7",
                600: "#9333ea",
              },
            },
            boxShadow: {
              soft: "0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)",
              hover:
                "0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04)",
            },
          },
        },
      };
    </script>
    <style>
      .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 10px;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #d1d5db;
      }
    </style>
  </head>
  <body
    class="bg-[#f8fafc] text-slate-800 font-sans h-screen flex overflow-hidden antialiased"
  >
    <!-- Sidebar -->
    <aside
      class="w-64 bg-white border-r border-slate-200 flex flex-col z-20 shadow-sm relative transition-all duration-300"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-100">
        <div class="flex items-center gap-3 cursor-pointer">
          <img src="{{ asset('usapayments.webp') }}" alt="USA Payments Logo" class="h-8 object-contain" />
          <span class="font-bold text-lg tracking-tight text-slate-900"
            >USA Payments</span
          >
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        <a
          href="#"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group"
        >
          <i
            class="fa-solid fa-chart-pie w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"
          ></i>
          <span class="font-medium text-sm">Dashboard</span>
        </a>

        <a id="nav-merchant" onclick="showTab('merchant', event)" href="#merchant" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg bg-teal-50 text-teal-700 font-medium transition-colors relative group shadow-sm"
        >
          <!-- Highlight indicator -->
          <div class="nav-indicator absolute left-0 top-0 bottom-0 w-1 bg-teal-500 rounded-r-full"></div>
          <i class="nav-icon fa-solid fa-border-all w-5 text-center text-teal-600"></i>
          <span class="text-sm">Merchant Management</span>
        </a>

        <div class="pt-4 pb-2 px-3">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Payments
          </p>
        </div>

        <a id="nav-online" onclick="showTab('online', event)" href="#online" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group cursor-pointer">
            <div class="nav-indicator absolute left-0 top-0 bottom-0 w-1 bg-teal-500 rounded-r-full hidden"></div>
            <i class="nav-icon fa-solid fa-dollar-sign w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"></i>
            <span class="font-medium text-sm">Online Payments</span>
          </a>

        <a id="nav-in-person" onclick="showTab('in-person', event)" href="#in-person" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group cursor-pointer">
            <div class="nav-indicator absolute left-0 top-0 bottom-0 w-1 bg-teal-500 rounded-r-full hidden"></div>
            <i class="nav-icon fa-solid fa-mobile-screen w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"></i>
            <span class="font-medium text-sm">In-Person Payments</span>
          </a>

        <a id="nav-ghl" onclick="showTab('ghl', event)" href="#ghl" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group cursor-pointer">
            <div class="nav-indicator absolute left-0 top-0 bottom-0 w-1 bg-teal-500 rounded-r-full hidden"></div>
            <i class="nav-icon fa-solid fa-users-gear w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"></i>
            <span class="font-medium text-sm">Clients-GHL</span>
          </a>

        <div class="pt-4 pb-2 px-3">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Overview
          </p>
        </div>

        <a id="nav-sales" onclick="showTab('sales', event)" href="#sales" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group cursor-pointer"
        >
          <div class="nav-indicator absolute left-0 top-0 bottom-0 w-1 bg-teal-500 rounded-r-full hidden"></div>
          <i
            class="nav-icon fa-solid fa-briefcase w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"
          ></i>
          <span class="font-medium text-sm">Sales Reps</span>
        </a>

        <a
          href="#"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group"
        >
          <i
            class="fa-solid fa-chart-line w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"
          ></i>
          <span class="font-medium text-sm">Reporting</span>
        </a>

        <a
          href="#"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors group"
        >
          <i
            class="fa-solid fa-gear w-5 text-center text-slate-400 group-hover:text-teal-500 transition-colors"
          ></i>
          <span class="font-medium text-sm">Account Settings</span>
        </a>
      </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 bg-[#f8fafc]">
      <!-- Top Navigation Bar -->
      <header
        class="h-16 bg-white border-b border-slate-200 flex flex-shrink-0 items-center justify-between px-6 z-10 shadow-sm relative"
      >
        <!-- Search Bar -->
        <div class="max-w-md w-full ml-4">
          <div class="relative">
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
              <i
                class="fa-solid fa-magnifying-glass text-slate-400 text-sm"
              ></i>
            </div>
            <input
              type="text"
              placeholder="Search Merchants, Gateways, or Transactions..."
              class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition duration-150 ease-in-out shadow-inner"
            />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <a
            href="https://forms.zohopublic.com/usapayments/form/ApplicationFormFinal/formperma/ZHflOaAoRSoV_XiXrCUEPpWod92mjwmsfeNxMem7L3s"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 shadow-sm hover:shadow-md transition-all duration-200 ring-1 ring-teal-500/50"
          >
            <i class="fa-solid fa-plus mr-2 text-xs"></i> Create New Merchant
            Application
          </a>

          <div
            class="flex items-center gap-3 text-slate-400 border-l border-slate-200 pl-4 ml-2"
          >
            <button class="hover:text-slate-600 transition-colors">
              <i class="fa-solid fa-share-nodes"></i>
            </button>

            <button class="relative hover:text-slate-600 transition-colors">
              <i class="fa-regular fa-bell"></i>
              <span
                class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
              ></span>
            </button>

            <button class="hover:text-slate-600 transition-colors">
              <i class="fa-regular fa-circle-question"></i>
            </button>
            <!-- Settings top -->
            <!-- Filter or display options maybe? I'll omit if not explicitly requested, replacing with profile border -->
          </div>

          <!-- Profile Dropdown Menu Placeholder -->
          <div
            class="flex items-center gap-3 ml-2 border-l border-slate-200 pl-4 cursor-pointer hover:bg-slate-50 p-1.5 rounded-lg transition-colors"
          >
            <div class="text-right hidden md:block">
              <div class="text-sm font-medium text-slate-900">Patsy Machin</div>
              <div class="text-xs text-slate-500 font-medium">
                Administrator
              </div>
            </div>
            <img
              class="h-9 w-9 rounded-full object-cover border-2 border-white shadow-sm ring-1 ring-slate-200"
              src="https://ui-avatars.com/api/?name=Patsy+Machin&background=f3e8ff&color=9333ea&rounded=true"
              alt="Profile"
            />
            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
          </div>
        </div>
      </header>

      <!-- Main Dashboard Content -->
      <main
        class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-8"
      >
        <div id="tab-merchant" class="max-w-7xl mx-auto space-y-6 tab-content block">
          <!-- Dashboard Header -->
          <div
            class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2"
          >
            <div>
              <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                Merchant Performance Overview
                <span class="text-slate-400 font-medium text-xl ml-2"
                  >- NMI API Integration Demo</span
                >
              </h1>

              <!-- API Status Widget -->
              <div
                class="mt-3 flex items-center bg-white border border-slate-200 rounded-lg shadow-sm px-3 py-2 w-fit"
              >
                <span
                  class="flex h-2.5 w-2.5 bg-green-500 rounded-full mr-2 shadow-[0_0_8px_rgba(34,197,94,0.6)]"
                ></span>
                <span class="text-xs font-semibold text-slate-700 mr-3"
                  >NMI API Connection Status:</span
                >
                <span
                  class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded"
                  >Last Synced: 3 minutes ago</span
                >
              </div>
            </div>

            <!-- Date Filter -->
            <div class="relative inline-block text-left" id="dateFilterContainer">
              <div
                onclick="toggleDropdown('dateDropdown')"
                class="flex items-center bg-white border border-slate-200 rounded-lg shadow-sm px-4 py-2 cursor-pointer hover:border-slate-300 transition-colors"
              >
                <i class="fa-regular fa-calendar text-slate-400 mr-2"></i>
                <span class="text-sm font-medium text-slate-700" id="datePickerLabel"
                  >2026-02-19 - 2026-02-25</span
                >
                <i
                  class="fa-solid fa-chevron-down text-xs text-slate-400 ml-3 transition-transform duration-200" id="dateDropdownIcon"
                ></i>
              </div>

              <!-- Dropdown menu -->
              <div
                id="dateDropdown"
                class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 hidden z-50 transition-all duration-200 opacity-0 transform scale-95"
              >
                <div class="py-1" role="none">
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors" onclick="selectDateRange('Today', event)">Today</a>
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors" onclick="selectDateRange('Yesterday', event)">Yesterday</a>
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors font-medium bg-slate-50 text-teal-600" id="defaultDateRange" onclick="selectDateRange('Last 7 Days', event)">Last 7 Days (2026-02-19 - 2026-02-25)</a>
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors" onclick="selectDateRange('Last 30 Days', event)">Last 30 Days</a>
                </div>
                <div class="py-1" role="none">
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors" onclick="selectDateRange('This Month', event)">This Month</a>
                  <a href="#" class="text-slate-700 block px-4 py-2 text-sm hover:bg-slate-50 hover:text-teal-600 transition-colors" onclick="selectDateRange('Last Month', event)">Last Month</a>
                </div>
              </div>
            </div>
          </div>

          <!-- KPI Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1 -->
            <div
              class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300 relative overflow-hidden group"
            >
              <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-teal-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-500"
              ></div>
              <h3 class="text-sm font-medium text-slate-500 relative z-10">
                Active Merchants
              </h3>
              <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span id="kpi-merchants" class="text-3xl font-bold text-slate-900">1,250</span>
                <span
                  class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded flex items-center"
                  ><i class="fa-solid fa-arrow-trend-up text-[10px] mr-1"></i>
                  4.2%</span
                >
              </div>
              <div class="h-10 mt-4 relative z-10">
                <canvas id="chartActiveMerchants"></canvas>
              </div>
            </div>

            <!-- Card 2 -->
            <div
              class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300 relative overflow-hidden group"
            >
              <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-500"
              ></div>
              <h3 class="text-sm font-medium text-slate-500 relative z-10">
                Total Transaction Volume
              </h3>
              <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span id="kpi-volume" class="text-3xl font-bold text-slate-900">$15.8M</span>
                <span
                  class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded flex items-center"
                  ><i class="fa-solid fa-arrow-trend-up text-[10px] mr-1"></i>
                  8.1%</span
                >
              </div>
              <div class="h-10 mt-4 relative z-10">
                <canvas id="chartVolume"></canvas>
              </div>
            </div>

            <!-- Card 3 -->
            <div
              class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300 flex flex-col justify-between"
            >
              <div>
                <h3 class="text-sm font-medium text-slate-500">
                  Average Approval Rate
                </h3>
                <div class="mt-2 flex items-baseline gap-2">
                  <span class="text-3xl font-bold text-slate-900">98.2%</span>
                  <span class="text-sm font-medium text-slate-500"
                    >of total reqs</span
                  >
                </div>
              </div>
              <div class="flex items-center gap-3 mt-4">
                <div class="h-12 w-12 flex-shrink-0">
                  <canvas id="chartApproval"></canvas>
                </div>
                <div class="text-xs text-slate-500 space-y-1">
                  <div class="flex items-center justify-between gap-4">
                    <span class="flex items-center"
                      ><span
                        class="w-2 h-2 rounded-full bg-teal-500 mr-1.5"
                      ></span
                      >Success</span
                    ><span class="font-medium text-slate-700">98.2%</span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <span class="flex items-center"
                      ><span
                        class="w-2 h-2 rounded-full bg-slate-200 mr-1.5"
                      ></span
                      >Decline</span
                    ><span class="font-medium text-slate-700">1.8%</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 4 -->
            <div
              class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300 relative overflow-hidden group"
            >
              <div
                class="absolute -right-4 -top-4 w-24 h-24 bg-teal-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-500"
              ></div>
              <h3 class="text-sm font-medium text-slate-500 relative z-10">
                New Merchant Applications
              </h3>
              <div class="mt-2 flex items-baseline gap-2 relative z-10">
                <span id="kpi-apps" class="text-3xl font-bold text-slate-900">45</span>
                <span
                  class="text-sm font-medium text-red-500 bg-red-50 px-1.5 py-0.5 rounded flex items-center"
                  ><i class="fa-solid fa-arrow-trend-down text-[10px] mr-1"></i>
                  2.1%</span
                >
              </div>
              <div class="h-10 mt-4 relative z-10">
                <canvas id="chartNewApps"></canvas>
              </div>
            </div>
          </div>

          <!-- Main Grid Layout for Lower Section -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Table (Takes up 2 columns) -->
            <div
              class="lg:col-span-2 bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden flex flex-col"
            >
              <!-- Table Header -->
              <div
                class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white"
              >
                <div>
                  <h2 class="text-lg font-bold text-slate-900">
                    Comerciantes y Transacciones - Informe Detallado
                  </h2>
                  <p class="text-sm text-slate-500 mt-0.5">
                    Visión general de la actividad de los comerciantes desde la
                    pasarela conectada.
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors"
                    title="View All"
                  >
                    <i class="fa-solid fa-expand"></i>
                  </button>
                  <button
                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors"
                    title="Filter"
                  >
                    <i class="fa-solid fa-filter"></i>
                  </button>
                  <button
                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors"
                    title="Export"
                  >
                    <i class="fa-solid fa-download"></i>
                  </button>
                </div>
              </div>

              <!-- Table Data -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead class="bg-slate-50">
                    <tr>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        ID (Pasarela / Comerciante)
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        Comerciante / Legal
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        Sedes
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        Transacciones / Vol
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        Estado
                      </th>
                      <th
                        scope="col"
                        class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide"
                      >
                        Acciones
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-slate-100">
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" onclick="openModal('merchantModal')">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm font-semibold text-slate-900 group-hover:text-teal-600 transition-colors cursor-pointer"
                            >GW-987654</span
                          >
                          <span class="text-xs text-slate-500">M-1020</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 h-8 w-8 rounded bg-teal-50 flex items-center justify-center text-teal-600 font-bold border border-teal-100 mr-3"
                          >
                            GS
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-800"
                              >Global Solutions</span
                            >
                            <span class="text-xs text-slate-500"
                              >Global Solutions LLC</span
                            >
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-800 flex items-start gap-1"
                            ><i
                              class="fa-solid fa-location-dot mt-0.5 text-slate-400 text-[10px]"
                            ></i>
                            <span class="truncate max-w-[150px]"
                              >456 Commerce Ave, Los Angeles, CA 90012</span
                            ></span
                          >
                          <span
                            class="text-xs font-medium text-slate-500 bg-slate-100 w-fit px-1.5 py-0.5 rounded mt-1"
                            >+10 Sedes</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-900 font-medium flex items-center"
                            ><i
                              class="fa-regular fa-credit-card text-slate-400 mr-1.5 text-xs"
                            ></i>
                            75,000 txns</span
                          >
                          <span
                            class="text-xs font-semibold text-teal-600 mt-0.5 ml-5"
                            >$8.5M</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"
                        >
                          <span
                            class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 shadow-sm"
                          ></span>
                          Activo
                        </span>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                      >
                        <div
                          class="flex items-center justify-end gap-3 text-slate-400"
                        >
                          <button
                            class="hover:text-teal-600 transition-colors p-1"
                          >
                            <i class="fa-regular fa-eye"></i>
                          </button>
                          <button
                            class="hover:text-purple-600 transition-colors p-1"
                          >
                            <i class="fa-solid fa-gear"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" onclick="openModal('merchantModal')">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm font-semibold text-slate-900 group-hover:text-teal-600 transition-colors cursor-pointer"
                            >GW-123456</span
                          >
                          <span class="text-xs text-slate-500">M-7890</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 h-8 w-8 rounded bg-purple-50 flex items-center justify-center text-purple-600 font-bold border border-purple-100 mr-3"
                          >
                            AC
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-800"
                              >Acme Corp</span
                            >
                            <span class="text-xs text-slate-500">Acme Inc</span>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-800 flex items-start gap-1"
                            ><i
                              class="fa-solid fa-location-dot mt-0.5 text-slate-400 text-[10px]"
                            ></i>
                            <span class="truncate max-w-[150px]"
                              >123 Main St, Springfield, IL 62704</span
                            ></span
                          >
                          <span
                            class="text-xs font-medium text-slate-500 bg-slate-100 w-fit px-1.5 py-0.5 rounded mt-1"
                            >+3 Sedes</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-900 font-medium flex items-center"
                            ><i
                              class="fa-regular fa-credit-card text-slate-400 mr-1.5 text-xs"
                            ></i>
                            15,200 txns</span
                          >
                          <span
                            class="text-xs font-semibold text-teal-600 mt-0.5 ml-5"
                            >$1.5M</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"
                        >
                          <span
                            class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 shadow-sm"
                          ></span>
                          Activo
                        </span>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                      >
                        <div
                          class="flex items-center justify-end gap-3 text-slate-400"
                        >
                          <button
                            class="hover:text-teal-600 transition-colors p-1"
                          >
                            <i class="fa-regular fa-eye"></i>
                          </button>
                          <button
                            class="hover:text-purple-600 transition-colors p-1"
                          >
                            <i class="fa-solid fa-gear"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" onclick="openModal('merchantModal')">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm font-semibold text-slate-900 group-hover:text-teal-600 transition-colors cursor-pointer"
                            >GW-456789</span
                          >
                          <span class="text-xs text-slate-500">M-2045</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-600 font-bold border border-slate-200 mr-3"
                          >
                            TN
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-800"
                              >TechNexus</span
                            >
                            <span class="text-xs text-slate-500"
                              >Nexus Technology Group LLC</span
                            >
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-800 flex items-start gap-1"
                            ><i
                              class="fa-solid fa-location-dot mt-0.5 text-slate-400 text-[10px]"
                            ></i>
                            <span class="truncate max-w-[150px]"
                              >88 Silicon Blvd, San Jose, CA 95112</span
                            ></span
                          >
                          <span
                            class="text-xs text-slate-400 mt-1 italic w-fit px-1"
                            >-</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-900 font-medium flex items-center"
                            ><i
                              class="fa-regular fa-credit-card text-slate-400 mr-1.5 text-xs"
                            ></i>
                            2,300 txns</span
                          >
                          <span
                            class="text-xs font-semibold text-teal-600 mt-0.5 ml-5"
                            >$450K</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200"
                        >
                          <span
                            class="w-1.5 h-1.5 rounded-full bg-yellow-400 mr-1.5 shadow-sm"
                          ></span>
                          En Revisión
                        </span>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                      >
                        <div
                          class="flex items-center justify-end gap-3 text-slate-400"
                        >
                          <button
                            class="hover:text-teal-600 transition-colors p-1"
                          >
                            <i class="fa-regular fa-eye"></i>
                          </button>
                          <button
                            class="hover:text-purple-600 transition-colors p-1"
                          >
                            <i class="fa-solid fa-gear"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Row 4 -->
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" onclick="openModal('merchantModal')">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm font-semibold text-slate-900 group-hover:text-teal-600 transition-colors cursor-pointer"
                            >GW-112233</span
                          >
                          <span class="text-xs text-slate-500">M-8001</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 h-8 w-8 rounded bg-teal-50 flex items-center justify-center text-teal-600 font-bold border border-teal-100 mr-3"
                          >
                            FL
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-800"
                              >Fresh Logistics</span
                            >
                            <span class="text-xs text-slate-500"
                              >Fresh Logistics Partners</span
                            >
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-800 flex items-start gap-1"
                            ><i
                              class="fa-solid fa-location-dot mt-0.5 text-slate-400 text-[10px]"
                            ></i>
                            <span class="truncate max-w-[150px]"
                              >900 River Rd, Dallas, TX 75001</span
                            ></span
                          >
                          <span
                            class="text-xs font-medium text-slate-500 bg-slate-100 w-fit px-1.5 py-0.5 rounded mt-1"
                            >+1 Sede</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-900 font-medium flex items-center"
                            ><i
                              class="fa-regular fa-credit-card text-slate-400 mr-1.5 text-xs"
                            ></i>
                            11,450 txns</span
                          >
                          <span
                            class="text-xs font-semibold text-teal-600 mt-0.5 ml-5"
                            >$1.2M</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"
                        >
                          <span
                            class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 shadow-sm"
                          ></span>
                          Activo
                        </span>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                      >
                        <div
                          class="flex items-center justify-end gap-3 text-slate-400"
                        >
                          <button
                            class="hover:text-teal-600 transition-colors p-1"
                          >
                            <i class="fa-regular fa-eye"></i>
                          </button>
                          <button
                            class="hover:text-purple-600 transition-colors p-1"
                          >
                            <i class="fa-solid fa-gear"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <!-- Row 5 -->
                    <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" onclick="openModal('merchantModal')">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm font-semibold text-slate-900 group-hover:text-teal-600 transition-colors cursor-pointer"
                            >GW-998877</span
                          >
                          <span class="text-xs text-slate-500">M-3005</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            class="flex-shrink-0 h-8 w-8 rounded bg-red-50 flex items-center justify-center text-red-600 font-bold border border-red-100 mr-3"
                          >
                            VD
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-semibold text-slate-800"
                              >Vertex Dynamics</span
                            >
                            <span class="text-xs text-slate-500"
                              >Vertex Dynamics Inc</span
                            >
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-800 flex items-start gap-1"
                            ><i
                              class="fa-solid fa-location-dot mt-0.5 text-slate-400 text-[10px]"
                            ></i>
                            <span class="truncate max-w-[150px]"
                              >202 Broadway, New York, NY 10038</span
                            ></span
                          >
                          <span
                            class="text-xs font-medium text-slate-500 bg-slate-100 w-fit px-1.5 py-0.5 rounded mt-1"
                            >+5 Sedes</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                          <span
                            class="text-sm text-slate-900 font-medium flex items-center"
                            ><i
                              class="fa-regular fa-credit-card text-slate-400 mr-1.5 text-xs"
                            ></i>
                            0 txns</span
                          >
                          <span
                            class="text-xs font-semibold text-slate-400 mt-0.5 ml-5"
                            >$0</span
                          >
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200"
                        >
                          <span
                            class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5 shadow-sm"
                          ></span>
                          Inactivo
                        </span>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                      >
                        <div
                          class="flex items-center justify-end gap-3 text-slate-400"
                        >
                          <button
                            class="hover:text-teal-600 transition-colors p-1"
                          >
                            <i class="fa-regular fa-eye"></i>
                          </button>
                          <button
                            class="hover:text-purple-600 transition-colors p-1"
                          >
                            <i class="fa-solid fa-gear"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div
                class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-center"
              >
                <button
                  class="text-sm text-teal-600 font-medium hover:text-teal-700"
                >
                  Ver todas las transacciones
                  <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                </button>
              </div>
            </div>

            <!-- Additional Widgets (Right Column) -->
            <div class="space-y-6">
              <!-- Channels Widget -->
              <div
                class="bg-white rounded-xl shadow-soft border border-slate-200 p-6 flex flex-col"
              >
                <h3 class="text-base font-bold text-slate-900 mb-5">
                  Canales de Pago
                </h3>
                <div
                  class="relative flex-1 flex items-center justify-center min-h-[160px]"
                >
                  <div class="w-40 h-40">
                    <canvas id="chartChannels"></canvas>
                  </div>
                  <div
                    class="absolute inset-0 flex flex-col items-center justify-center"
                  >
                    <span class="text-xl font-bold text-slate-800">100%</span>
                    <span
                      class="text-[10px] text-slate-500 font-semibold uppercase tracking-widest"
                      >Volumen</span
                    >
                  </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3">
                  <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-3 h-3 rounded-md bg-teal-500 shadow-sm border border-teal-600"
                      ></div>
                      <span class="text-slate-600 font-medium"
                        >Card-Present</span
                      >
                    </div>
                    <span class="font-bold text-slate-800">10%</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-3 h-3 rounded-md bg-purple-500 shadow-sm border border-purple-600"
                      ></div>
                      <span class="text-slate-600 font-medium"
                        >Online (eCommerce)</span
                      >
                    </div>
                    <span class="font-bold text-slate-800">68%</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                      <div
                        class="w-3 h-3 rounded-md bg-slate-200 border border-slate-300"
                      ></div>
                      <span class="text-slate-600 font-medium"
                        >Alternative Payments</span
                      >
                    </div>
                    <span class="font-bold text-slate-800">22%</span>
                  </div>
                </div>
              </div>

              <!-- Recently Boarded Widget -->
              <div
                class="bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden"
              >
                <div
                  class="px-6 py-4 border-b border-slate-100 flex justify-between items-center"
                >
                  <h3 class="text-base font-bold text-slate-900">
                    Recently Boarded
                  </h3>
                  <button
                    class="text-xs text-teal-600 hover:text-teal-700 font-medium"
                  >
                    View All
                  </button>
                </div>
                <div class="divide-y divide-slate-100">
                  <!-- Merchant 1 -->
                  <div
                    class="p-4 flex items-center gap-4 hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <div
                      class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                    >
                      <i class="fa-solid fa-store text-slate-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-slate-800 truncate">
                        Quantum Retailer
                      </p>
                      <p class="text-xs text-slate-500">Terminal & Online</p>
                    </div>
                    <div class="text-right">
                      <p class="text-xs font-semibold text-slate-700">Today</p>
                      <p class="text-[10px] text-green-600 font-medium">
                        Approved
                      </p>
                    </div>
                  </div>
                  <!-- Merchant 2 -->
                  <div
                    class="p-4 flex items-center gap-4 hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <div
                      class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                    >
                      <i class="fa-solid fa-globe text-slate-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-slate-800 truncate">
                        SaaS Builders Co.
                      </p>
                      <p class="text-xs text-slate-500">eCommerce Only</p>
                    </div>
                    <div class="text-right">
                      <p class="text-xs font-semibold text-slate-700">
                        Yesterday
                      </p>
                      <p class="text-[10px] text-green-600 font-medium">Live</p>
                    </div>
                  </div>
                  <!-- Merchant 3 -->
                  <div
                    class="p-4 flex items-center gap-4 hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <div
                      class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                    >
                      <i class="fa-solid fa-utensils text-slate-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-slate-800 truncate">
                        Local Diner LLC
                      </p>
                      <p class="text-xs text-slate-500">In-Person Only</p>
                    </div>
                    <div class="text-right">
                      <p class="text-xs font-semibold text-slate-700">Feb 24</p>
                      <p class="text-[10px] text-yellow-600 font-medium">
                        Pending
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          
          </div> <!-- End tab-merchant -->
          
          <!-- Tab: Online Payments -->
          <div id="tab-online" class="max-w-7xl mx-auto space-y-6 tab-content hidden">
             <!-- Dashboard Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2">
              <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Online Payments Performance</h1>
              </div>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">eCommerce Volume</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-purple-600">$10.7M</span>
                        <span class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded flex items-center"><i class="fa-solid fa-arrow-trend-up text-[10px] mr-1"></i> 12.4%</span>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Active Gateways</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900">890</span>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Average Ticket Size</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900">$142.50</span>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Chargeback Ratio</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-red-500">0.8%</span>
                        <span class="text-sm font-medium text-red-600 bg-red-50 px-1.5 py-0.5 rounded flex items-center"><i class="fa-solid fa-arrow-trend-up text-[10px] mr-1"></i> 0.1%</span>
                    </div>
                </div>
            </div>
            
            <!-- Table and Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                        <h2 class="text-lg font-bold text-slate-900">Recent Online Transactions</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Order ID</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Method</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">ORD-89211</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$1,250.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-brands fa-apple mr-2"></i> Apple Pay</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">ORD-89210</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$45.50</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-brands fa-cc-visa text-blue-600 mr-2"></i> Visa ...4242</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">ORD-89209</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$890.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-brands fa-cc-mastercard text-orange-500 mr-2"></i> MC ...5555</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">Declined</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">ORD-89208</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$120.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-brands fa-paypal text-blue-400 mr-2"></i> PayPal</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-soft border border-slate-200 p-6 flex flex-col">
                    <h3 class="text-base font-bold text-slate-900 mb-5">Online Methods</h3>
                    <div class="relative flex-1 flex items-center justify-center min-h-[160px]">
                        <div class="w-40 h-40">
                            <canvas id="chartOnlineMethods"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          </div>
          
          <!-- Tab: In-Person Payments -->
          <div id="tab-in-person" class="max-w-7xl mx-auto space-y-6 tab-content hidden">
             <!-- Dashboard Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2">
              <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">In-Person Payments Performance</h1>
              </div>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Terminal Volume</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-teal-600">$5.1M</span>
                        <span class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded flex items-center"><i class="fa-solid fa-arrow-trend-up text-[10px] mr-1"></i> 2.1%</span>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Active Terminals</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900">3,450</span>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Contactless Mix</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900">72%</span>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Terminal Errors</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-green-500">0.2%</span>
                        <span class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded flex items-center">System Healthy</span>
                    </div>
                </div>
            </div>
            
            <!-- Table and Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                        <h2 class="text-lg font-bold text-slate-900">Recent Terminal Transactions</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Terminal ID</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Entry</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">TERM-X001</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$25.50</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-solid fa-wifi mr-2"></i> Contactless</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">TERM-X045</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$109.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-solid fa-credit-card mr-2"></i> Chip Insert</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                                <tr class="hover:bg-slate-50 cursor-pointer">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">TERM-NY12</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">$12.99</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><i class="fa-solid fa-wifi mr-2"></i> Mobile Tap</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Success</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-soft border border-slate-200 p-6 flex flex-col">
                    <h3 class="text-base font-bold text-slate-900 mb-5">Terminal Activity</h3>
                    <div class="relative flex-1 flex items-center justify-center min-h-[160px]">
                        <div class="w-40 h-40">
                            <canvas id="chartTerminalActivity"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          </div>

          <!-- Tab: Clients-GHL -->
          <div id="tab-ghl" class="max-w-7xl mx-auto space-y-6 tab-content hidden">
             <!-- Dashboard Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2">
              <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Clients-GHL Management</h1>
                <p class="text-sm text-slate-500 mt-1">HighLevel integration status and billing performance</p>
              </div>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Total GHL Clients</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-teal-600">342</span>
                        <span class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded">+12</span>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Premium Plan Mix</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-purple-600">64%</span>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Monthly Billing</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900">$84.2K</span>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Merchant Account</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-teal-500">92%</span>
                        <span class="text-xs text-slate-400">Coverage</span>
                    </div>
                </div>
            </div>
            
            <!-- Client Table -->
            <div class="bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-bold text-slate-900">GHL Integrated Clients</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Client</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">GHL Status</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Plan</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Monthly Billing</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Merchant Account</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold mr-3 text-xs">NK</div>
                                        <span class="text-sm font-semibold text-slate-900">Nova Kitchens</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                        <i class="fa-solid fa-circle text-[6px] mr-1.5"></i> Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 rounded bg-purple-50 text-purple-700 font-medium border border-purple-100 text-xs">Premium</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$12,450.00</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-teal-600 font-bold text-sm"><i class="fa-solid fa-check-circle mr-1"></i> Yes</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-bold mr-3 text-xs">SS</div>
                                        <span class="text-sm font-semibold text-slate-900">Starlight Spa</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                        <i class="fa-solid fa-circle text-[6px] mr-1.5"></i> Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-medium border border-slate-200 text-xs">Silver</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$4,200.00</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-teal-600 font-bold text-sm"><i class="fa-solid fa-check-circle mr-1"></i> Yes</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold mr-3 text-xs">TB</div>
                                        <span class="text-sm font-semibold text-slate-900">Tech Blue</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                        <i class="fa-solid fa-circle text-[6px] mr-1.5"></i> Inactive
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 rounded bg-purple-50 text-purple-700 font-medium border border-purple-100 text-xs">Premium</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$0.00</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-slate-400 font-bold text-sm"><i class="fa-solid fa-circle-xmark mr-1"></i> No</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>

          <!-- Tab: Sales Reps -->
          <div id="tab-sales" class="max-w-7xl mx-auto space-y-6 tab-content hidden">
             <!-- Dashboard Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-2">
              <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sales Representatives & Referrals</h1>
                <p class="text-sm text-slate-500 mt-1">Performance tracking for advisors and referred merchant activity</p>
              </div>
            </div>
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Active Advisors</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-teal-600">12</span>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">New Referrals (MTD)</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-purple-600">28</span>
                        <span class="text-sm font-medium text-green-600 bg-green-50 px-1.5 py-0.5 rounded">+15%</span>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Total Referred Volume</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span id="kpi-sales-volume" class="text-3xl font-bold text-slate-900">$2.4M</span>
                    </div>
                </div>
                <!-- Card 4 -->
                <div class="bg-white rounded-xl shadow-soft border border-slate-100 p-5 hover:shadow-hover transition-shadow duration-300">
                    <h3 class="text-sm font-medium text-slate-500">Avg. Commission</h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-teal-500">15%</span>
                    </div>
                </div>
            </div>
            
            <!-- Sales Rep Table -->
            <div class="bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                    <h2 class="text-lg font-bold text-slate-900">Advisor Performance List</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Advisor</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Referrals</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Primary Focus</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Monthly Ref. Billing</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Referral</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <!-- 10 Demo Records -->
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold mr-3 text-xs">JD</div><span class="text-sm font-semibold text-slate-900">John Davis</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">8 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">GHL Expert</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$45,200.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">2 hours ago</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold mr-3 text-xs">SM</div><span class="text-sm font-semibold text-slate-900">Sarah Miller</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">12 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-teal-50 text-teal-700 text-xs font-medium">Merchant Accounts</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$128,400.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Yesterday</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-bold mr-3 text-xs">RW</div><span class="text-sm font-semibold text-slate-900">Robert Wilson</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">5 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">GHL Expert</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$12,400.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Feb 24, 2026</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold mr-3 text-xs">EL</div><span class="text-sm font-semibold text-slate-900">Emily Loft</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">15 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-teal-50 text-teal-700 text-xs font-medium">Hybrid Agent</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$210,000.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">3 days ago</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-700 font-bold mr-3 text-xs">MT</div><span class="text-sm font-semibold text-slate-900">Michael Taylor</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">3 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs font-medium">New Associate</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$1,800.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Feb 20, 2026</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-bold mr-3 text-xs">CA</div><span class="text-sm font-semibold text-slate-900">Chris Anderson</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">10 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-teal-50 text-teal-700 text-xs font-medium">Merchant Specialist</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$89,300.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Feb 22, 2026</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold mr-3 text-xs">LS</div><span class="text-sm font-semibold text-slate-900">Linda Smith</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">22 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">Top Producer</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$340,500.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Today</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-bold mr-3 text-xs">DB</div><span class="text-sm font-semibold text-slate-900">David Brown</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">6 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-teal-50 text-teal-700 text-xs font-medium">Regional Lead</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$22,900.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">4 days ago</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold mr-3 text-xs">PK</div><span class="text-sm font-semibold text-slate-900">Patricia King</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">9 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-purple-50 text-purple-700 text-xs font-medium">GHL Expert</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$61,000.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Yesterday</td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="openModal('salesModal')">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center"><div class="h-8 w-8 rounded-full bg-cyan-100 flex items-center justify-center text-cyan-700 font-bold mr-3 text-xs">TH</div><span class="text-sm font-semibold text-slate-900">Thomas Hill</span></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">4 Clients</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs font-medium">Associate Agent</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">$9,500.00</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">Feb 18, 2026</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
          
          
            <script>
                function showTab(tabId, event) {
                    if (event) {
                        event.preventDefault();
                    }

                    const targetTab = document.getElementById('tab-' + tabId);
                    if (!targetTab) {
                        return;
                    }

                    document.querySelectorAll('.tab-content').forEach(el => {
                        el.classList.remove('block');
                        el.classList.add('hidden');
                    });
                    
                    targetTab.classList.remove('hidden');
                    targetTab.classList.add('block');
                    
                    document.querySelectorAll('.nav-item').forEach(el => {
                        el.className = "nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors relative group shadow-none";
                        const indicator = el.querySelector('.nav-indicator');
                        if(indicator) indicator.classList.add('hidden');
                        
                        const icon = el.querySelector('.nav-icon');
                        if (icon) {
                            icon.classList.remove('text-teal-600', 'text-purple-600');
                            if (!icon.classList.contains('text-slate-400')) {
                                icon.classList.add('text-slate-400');
                            }
                        }
                    });
                    
                    const activeNav = document.getElementById('nav-' + tabId);
                    if (!activeNav) {
                        return;
                    }
                    activeNav.className = "nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg bg-teal-50 text-teal-700 font-medium transition-colors relative group shadow-sm";
                    
                    const activeIndicator = activeNav.querySelector('.nav-indicator');
                    if(activeIndicator) activeIndicator.classList.remove('hidden');
                    
                    const activeIcon = activeNav.querySelector('.nav-icon');
                    if(activeIcon) {
                        activeIcon.classList.remove('text-slate-400');
                        activeIcon.classList.add('text-teal-600');
                    }

                    if (window.location.hash !== '#' + tabId) {
                        window.location.hash = tabId;
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    const initialTab = window.location.hash.replace('#', '') || 'merchant';
                    showTab(initialTab);
                });

                window.addEventListener('hashchange', function () {
                    const hashTab = window.location.hash.replace('#', '') || 'merchant';
                    showTab(hashTab);
                });
            </script>

            <div class="max-w-7xl mx-auto">
          <!-- Footer -->
          <div class="pt-8 pb-4 border-t border-slate-200 mt-8 text-center">

            <p class="text-xs text-slate-400 font-medium tracking-wide">
              ©2026 NMI API Powered Demo | All data is simulated for
              presentation purposes.
            </p>
          </div>
        </div>
      </main>
    </div>

    <!-- Chart Configuration Script -->
    <script>
      // Common Chart Defaults
      Chart.defaults.font.family = "'Inter', sans-serif";
      Chart.defaults.color = "#94a3b8";
      Chart.defaults.plugins.tooltip.backgroundColor = "rgba(15, 23, 42, 0.9)";
      Chart.defaults.plugins.tooltip.padding = 10;
      Chart.defaults.plugins.tooltip.cornerRadius = 8;
      Chart.defaults.plugins.tooltip.displayColors = false;

      // 1. Active Merchants Mini Line Chart
      const ctxActive = document
        .getElementById("chartActiveMerchants")
        .getContext("2d");
      const gradientActive = ctxActive.createLinearGradient(0, 0, 0, 40);
      gradientActive.addColorStop(0, "rgba(20, 184, 166, 0.2)");
      gradientActive.addColorStop(1, "rgba(20, 184, 166, 0)");

      new Chart(ctxActive, {
        type: "line",
        data: {
          labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
          datasets: [
            {
              data: [1050, 1100, 1120, 1180, 1200, 1250],
              borderColor: "#14b8a6", // teal-500
              backgroundColor: gradientActive,
              borderWidth: 2,
              tension: 0.4,
              pointRadius: 0,
              pointHoverRadius: 4,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: true } },
          scales: {
            x: { display: false },
            y: { display: false, min: 1000 },
          },
          interaction: { mode: "index", intersect: false },
        },
      });

      // 2. Transaction Volume Mini Bar Chart
      new Chart(document.getElementById("chartVolume").getContext("2d"), {
        type: "bar",
        data: {
          labels: ["M", "T", "W", "T", "F", "S", "S"],
          datasets: [
            {
              data: [1.2, 1.9, 1.5, 2.1, 2.8, 3.5, 2.8],
              backgroundColor: "#a855f7", // purple-500
              borderRadius: 2,
              barPercentage: 0.6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { display: false },
            y: { display: false },
          },
        },
      });

      // 3. Approval Rate Pie Chart
      new Chart(document.getElementById("chartApproval").getContext("2d"), {
        type: "doughnut",
        data: {
          labels: ["Success", "Decline"],
          datasets: [
            {
              data: [98.2, 1.8],
              backgroundColor: ["#14b8a6", "#f1f5f9"], // teal-500, slate-100
              borderWidth: 0,
              hoverOffset: 2,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "75%",
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
        },
      });

      // 4. New Applications Mini Line Chart
      const ctxNew = document.getElementById("chartNewApps").getContext("2d");
      const gradientNew = ctxNew.createLinearGradient(0, 0, 0, 40);
      gradientNew.addColorStop(0, "rgba(20, 184, 166, 0.2)");
      gradientNew.addColorStop(1, "rgba(20, 184, 166, 0)");

      new Chart(ctxNew, {
        type: "line",
        data: {
          labels: ["W1", "W2", "W3", "W4"],
          datasets: [
            {
              data: [35, 42, 38, 45],
              borderColor: "#14b8a6", // teal-500
              backgroundColor: gradientNew,
              borderWidth: 2,
              tension: 0.4,
              pointRadius: 0,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { display: false },
            y: { display: false, min: 20 },
          },
        },
      });

      // 5. Channels Doughnut Chart
      new Chart(document.getElementById("chartChannels").getContext("2d"), {
        type: "doughnut",
        data: {
          labels: ["Card-Present", "Online", "Alternative"],
          datasets: [
            {
              data: [10, 68, 22],
              backgroundColor: [
                "#14b8a6", // teal-500
                "#a855f7", // purple-500
                "#e2e8f0", // slate-200
              ],
              borderWidth: 2,
              borderColor: "#ffffff",
              hoverOffset: 4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "75%",
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (context) {
                  return " " + context.label + ": " + context.parsed + "%";
                },
              },
            },
          },
        },
      });
    
        // 6. Online Methods
        new Chart(document.getElementById('chartOnlineMethods').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Credit Card', 'Apple Pay', 'PayPal', 'Other'],
                datasets: [{
                    data: [55, 30, 10, 5],
                    backgroundColor: ['#a855f7', '#14b8a6', '#3b82f6', '#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
        });

        // 7. Terminal Activity
        new Chart(document.getElementById('chartTerminalActivity').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Smart POS', 'mPOS', 'PIN Pad'],
                datasets: [{
                    data: [60, 25, 15],
                    backgroundColor: ['#14b8a6', '#0ea5e9', '#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
        });
        
    </script>
    
    <!-- Merchant Details Modal -->
    <div id="merchantModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity opacity-0 duration-300 backdrop-blur-sm" onclick="closeModal('merchantModal')"></div>
        
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 duration-300 flex flex-col max-h-[90vh]">
                    
                    <!-- Header -->
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-3" id="modal-title">
                                Global Solutions LLC
                                <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-200 text-slate-700">M-1020</span>
                            </h3>
                            <div class="mt-1 flex items-center gap-4 text-sm text-slate-500">
                                <span><i class="fa-solid fa-location-dot mr-1"></i> 12 Active Locations</span>
                                <span class="flex items-center text-green-700 font-medium"><i class="fa-solid fa-circle text-[8px] mr-1.5 text-green-500"></i> Gateway Connected</span>
                            </div>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors bg-white hover:bg-slate-100 rounded-full p-2" onclick="closeModal('merchantModal')">
                            <i class="fa-solid fa-xmark w-5 h-5 flex items-center justify-center"></i>
                        </button>
                    </div>
                    
                    <!-- Body: Scrollable Content -->
                    <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
                        
                        <!-- Modal KPIs -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="border border-slate-100 rounded-lg p-4 bg-slate-50">
                                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">MTD Volume</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">$412,500</p>
                            </div>
                            <div class="border border-slate-100 rounded-lg p-4 bg-slate-50">
                                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Transactions</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">2,845</p>
                            </div>
                            <div class="border border-slate-100 rounded-lg p-4 bg-slate-50">
                                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Ticket</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">$145.00</p>
                            </div>
                        </div>

                        <!-- Transaction Table -->
                        <h4 class="text-sm font-bold text-slate-900 mb-3 border-b border-slate-100 pb-2">Recent Transactions (Cross-Location)</h4>
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date/Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">Today, 2:45 PM</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">Miami Store #04</td>
                                        <td class="px-4 py-3 text-slate-500"><i class="fa-brands fa-cc-visa text-blue-800 mr-2 text-lg"></i> •••• 4242</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$125.50</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">Approved</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">Today, 1:12 PM</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">NYC Flagship</td>
                                        <td class="px-4 py-3 text-slate-500"><i class="fa-brands fa-apple text-slate-800 mr-2 text-lg"></i> Apple Pay</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$89.00</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">Approved</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">Today, 11:30 AM</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">Online Store</td>
                                        <td class="px-4 py-3 text-slate-500"><i class="fa-brands fa-cc-mastercard text-orange-600 mr-2 text-lg"></i> •••• 5555</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$1,250.00</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">Declined</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">Yesterday, 5:45 PM</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">Dallas Branch</td>
                                        <td class="px-4 py-3 text-slate-500"><i class="fa-brands fa-cc-amex text-blue-500 mr-2 text-lg"></i> •••• 3782</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$45.20</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700">Approved</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Rep Details Modal -->
    <div id="salesModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity opacity-0 duration-300 backdrop-blur-sm" onclick="closeModal('salesModal')"></div>
        
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 duration-300 flex flex-col max-h-[90vh]">
                    
                    <!-- Header -->
                    <div class="bg-indigo-50 px-6 py-5 border-b border-indigo-100 flex justify-between items-start shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-lg shadow-sm border border-indigo-300">SM</div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" id="modal-title">Sarah Miller</h3>
                                <div class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-xs font-medium shadow-sm">Merchant Accounts</span>
                                    <span>• 12 Total Referrals</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors bg-white hover:bg-slate-100 rounded-full p-2" onclick="closeModal('salesModal')">
                            <i class="fa-solid fa-xmark w-5 h-5 flex items-center justify-center"></i>
                        </button>
                    </div>
                    
                    <!-- Body: Scrollable Content -->
                    <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
                        
                        <!-- Client Table -->
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-base font-bold text-slate-900">Referred Clients Portfolio</h4>
                            <span class="text-sm font-semibold text-teal-600 bg-teal-50 px-3 py-1 rounded-full border border-teal-100">Total Billing: $128,400.00</span>
                        </div>
                        
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Client Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Signup Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Plan / Integration</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">MTD Volume</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                                    <tr class="hover:bg-slate-50 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-slate-900 flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">EH</div>
                                            Elite Hardware
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Jan 12, 2026</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">Premium GHL</span></td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$45,000.00</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-slate-900 flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">BB</div>
                                            Burger Boss
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Dec 05, 2025</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">Retail Merchant</span></td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$62,150.00</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-slate-900 flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">SF</div>
                                            Smile Fitness
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Feb 02, 2026</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">Start GHL</span></td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$12,400.00</td>
                                    </tr>
                                    <tr class="hover:bg-slate-50 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-slate-900 flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">LC</div>
                                            Local Cafe
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">Feb 20, 2026</td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">eCommerce</span></td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">$8,850.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Interactive Modals & Dropdown Logic ---
        
        // Date Dropdown
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id + 'Icon');
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                setTimeout(() => { el.classList.remove('opacity-0', 'scale-95'); }, 10);
                if (icon) icon.classList.add('rotate-180');
            } else {
                el.classList.add('opacity-0', 'scale-95');
                if (icon) icon.classList.remove('rotate-180');
                setTimeout(() => { el.classList.add('hidden'); }, 200);
            }
        }
        
        document.addEventListener('click', function(event) {
            const container = document.getElementById('dateFilterContainer');
            if (container && !container.contains(event.target)) {
                const dropdown = document.getElementById('dateDropdown');
                const icon = document.getElementById('dateDropdownIcon');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('opacity-0', 'scale-95');
                    if(icon) icon.classList.remove('rotate-180');
                    setTimeout(() => { dropdown.classList.add('hidden'); }, 200);
                }
            }
        });

        function selectDateRange(rangeText, event) {
            event.preventDefault();
            document.getElementById('datePickerLabel').textContent = rangeText;
            toggleDropdown('dateDropdown');
            // Simulate data reload
            simulateLoading();
        }

        function simulateLoading() {
            document.body.style.cursor = 'wait';
            
            // Collect elements to update securely by ID
            const activeMerchantsEl = document.getElementById('kpi-merchants');
            const txVolumeEl = document.getElementById('kpi-volume');
            const newAppsEl = document.getElementById('kpi-apps');
            const salesVolEl = document.getElementById('kpi-sales-volume');
            
            setTimeout(() => { 
                document.body.style.cursor = 'default'; 
                
                // Randomize numbers slightly to simulate new data
                if (activeMerchantsEl) activeMerchantsEl.textContent = (1200 + Math.floor(Math.random() * 100)).toLocaleString();
                if (txVolumeEl) txVolumeEl.textContent = '$' + (10 + Math.random() * 8).toFixed(1) + 'M';
                if (newAppsEl) newAppsEl.textContent = Math.floor(20 + Math.random() * 40).toString();
                if (salesVolEl) salesVolEl.textContent = '$' + (1 + Math.random() * 2).toFixed(1) + 'M';
                
            }, 600);
        }

        // Modals
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const backdrop = modal.children[0];
                const panel = modal.querySelector('.relative.transform');
                
                modal.classList.remove('hidden');
                modal.style.zIndex = '99999';
                
                setTimeout(() => { 
                    if (backdrop) backdrop.classList.remove('opacity-0'); 
                    if (panel) {
                        panel.classList.remove('opacity-0', 'translate-y-4', 'sm:scale-95');
                        panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
                    }
                }, 10);
            }
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const backdrop = modal.children[0];
                const panel = modal.querySelector('.relative.transform');
                
                if (backdrop) backdrop.classList.add('opacity-0');
                if (panel) {
                    panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
                    panel.classList.add('opacity-0', 'translate-y-4', 'sm:scale-95');
                }
                
                setTimeout(() => { modal.classList.add('hidden'); }, 300);
            }
        };
        
        // Close modals on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal('merchantModal');
                closeModal('salesModal');
            }
        });
    </script>
</body>
</html>
