async function loadDashboard() {

    try {

        const response = await fetch('/api/merchants/1/dashboard');

        if (!response.ok) {
            throw new Error(
                'Unable to load dashboard data.'
            );
        }

        const data = await response.json();
        renderDashboard(data);

    } catch (error) {

        const errorBox = document.getElementById('error');
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}


function renderDashboard(data) {

    /*
     * Current cycle usage
     */

    document.getElementById('current-usage').textContent = Number(data.current_cycle_usage || 0).toLocaleString();
    document.getElementById('included-usage').textContent = Number(data.current_cycle_included_units || 0).toLocaleString();


    /*
     * Projected overage
     */

    document.getElementById(
        'projected-overage'
    ).textContent =
        Number(
            data.projected_overage_revenue || 0
        ).toLocaleString(
            'en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
        );


    /*
     * Active plan
     */

    const plan = data.active_plan || {};

    if (plan.billing_cycle) {

        document.getElementById('active-plan').textContent = `${plan.name} — ${plan.billing_cycle}`;
    } else {

        document.getElementById('active-plan').textContent = plan.name || 'N/A';
    }


    /*
     * Top customers
     */

    const topCustomers = document.getElementById('top-customers');
    topCustomers.innerHTML = '';


    if (!data.top_customers || data.top_customers.length === 0) {

        topCustomers.innerHTML = `
                <tr>
                    <td colspan="3" class="empty">
                        No usage data available.
                    </td>
                </tr>
            `;

    } else {

        data.top_customers.forEach(
            customer => {

                const customerName =
                    customer.customer?.name ||
                    `Customer #${customer.customer_id}`;

                topCustomers.innerHTML += `
                        <tr>
                            <td>
                                ${customerName}
                            </td>

                            <td>
                                ${Number(
                    customer.total_units
                ).toLocaleString()}
                            </td>

                            <td>
                                ${Number(
                    customer.percentage_of_allowance || 0
                ).toFixed(2)}%
                            </td>
                        </tr>
                    `;
            }
        );
    }


    /*
     * Churn risk
     */

    const churnCustomers = document.getElementById('churn-customers');
    churnCustomers.innerHTML = '';


    if (!data.usage_drop_customers || data.usage_drop_customers.length === 0) {

        churnCustomers.innerHTML = `
                <div class="empty">
                    No customers with usage drop above 50%.
                </div>
            `;

    } else {

        data.usage_drop_customers.forEach(
            customer => {

                churnCustomers.innerHTML += `
                        <div class="churn-item">
                            ${customer.customer_name}
                            —
                            ${customer.drop_percentage}% drop
                        </div>
                    `;
            }
        );
    }


    /*
     * Daily usage chart
     */

    renderUsageChart(
        data.daily_usage_trend || []
    );
}


function renderUsageChart(data) {

    const line = document.getElementById('usage-line');

    if (!data.length) {

        line.setAttribute(
            'points',
            ''
        );

        return;
    }


    const values =
        data.map(
            item => Number(
                item.total_units
            )
        );

    const maxValue =
        Math.max(...values, 1);

    const width = 1000;
    const height = 220;

    const padding = 10;

    const points =
        data.map(
            (item, index) => {

                const x =
                    data.length === 1 ?
                        width / 2 :
                        (
                            index /
                            (data.length - 1)
                        ) * width;

                const y =
                    height -
                    padding -
                    (
                        Number(
                            item.total_units
                        ) / maxValue
                    ) *
                    (height - padding * 2);

                return `${x},${y}`;
            }
        )
            .join(' ');

    line.setAttribute(
        'points',
        points
    );
}


loadDashboard(); 