<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Merchant Dashboard</title>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>

<body>

    <div class="container">

        <!-- Header -->

        <div class="header">
            <h1>
                Merchant Dashboard
                <span>— Acme Technologies</span>
            </h1>

            <span>Usage & Billing Overview</span>
        </div>

        <div id="error" class="error"></div>

        <!-- Summary Cards -->

        <div class="summary-grid">

            <div class="summary-card blue">

                <div class="summary-title">
                    Current Cycle Usage
                </div>

                <div class="summary-value">
                    <span id="current-usage">0</span>
                    /
                    <span id="included-usage">0</span>
                    units
                </div>

            </div>


            <div class="summary-card orange">

                <div class="summary-title">
                    Projected Overage Revenue
                </div>

                <div class="summary-value">
                    ₹<span id="projected-overage">0.00</span>
                </div>

            </div>


            <div class="summary-card green">

                <div class="summary-title">
                    Active Plan
                </div>

                <div class="summary-value">
                    <span id="active-plan">Loading...</span>
                </div>

            </div>

        </div>


        <!-- Top Customers + Churn Risk -->

        <div class="main-grid">

            <div class="panel">

                <div class="panel-title">
                    Top 5 Customers by Usage (this cycle)
                </div>

                <table>

                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Usage</th>
                            <th>% of Allowance</th>
                        </tr>
                    </thead>

                    <tbody id="top-customers">

                        <tr>
                            <td colspan="3" class="empty">
                                Loading...
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>


            <div class="panel churn-panel">

                <div class="churn-title">
                    ⚠ Churn Risk (usage ↓ &gt; 50% MoM)
                </div>

                <div id="churn-customers">
                    <div class="empty">
                        Loading...
                    </div>
                </div>

            </div>

        </div>


        <!-- Daily Usage Trend -->

        <div class="main-grid">

            <div class="panel trend-panel">

                <div class="panel-title">
                    Daily Usage Trend (last 30 days)
                </div>

                <div class="chart">

                    <div class="chart-grid"></div>

                    <div class="chart-label">
                        usage / day
                    </div>

                    <svg
                        id="usage-chart"
                        viewBox="0 0 1000 220"
                        preserveAspectRatio="none">
                        <polyline
                            id="usage-line"
                            fill="none"
                            stroke="#2878c8"
                            stroke-width="3"
                            points="" />
                    </svg>

                </div>

            </div>


            <!-- System Status -->

            <div class="panel system-panel">

                <div class="system-title">
                    System status (informational)
                </div>

                <div class="system-item">
                    Plan pricing cache:
                    <span class="status-ok">Active</span>
                </div>

                <div class="system-item">
                    Daily aggregation job:
                    <span class="status-ok">Queued</span>
                </div>

                <div class="system-item">
                    Usage endpoint:
                    <span class="status-ok">Rate limited</span>
                </div>

                <div class="system-item">
                    Rate limit:
                    <span>120 requests/min per merchant</span>
                </div>

            </div>

        </div>

    </div>


    <script src="{{ asset('js/dashboard.js') }}"></script>

</body>

</html>