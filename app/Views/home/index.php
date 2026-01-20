<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>

<style>
    .tree {
        --spacing: 1.5rem;
        --radius: 4px;
    }

    .tree-root {
        --depth: 0;
    }

    .tree ul {
        margin: 0;
        padding: 0;
        list-style: none;
        padding-left: calc(var(--spacing) + 1rem);
    }

    .tree li {
        margin: 0.25rem 0;
    }

    .tree-item-label {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
        gap: 0.5rem;
    }

    .tree-item-label:hover {
        background-color: #f0f0f0;
        color: #007bff;
    }

    .tree-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: bold;
        font-size: 1.1rem;
        line-height: 1;
    }

    .tree-toggle::before {
        content: '−';
        color: #007bff;
    }

    .tree-toggle.collapsed::before {
        content: '+';
    }

    .tree-children {
        display: block;
        max-height: 1000px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .tree-children.collapsed {
        max-height: 0;
        overflow: hidden;
    }

    .tree-leaf {
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        color: #007bff;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .tree-leaf:hover {
        background-color: #e7f3ff;
        text-decoration: none;
        color: #0056b3;
    }

    .tree-icon {
        width: 1.25rem;
        height: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div style="margin-top: 30px;">
    <!-- Tree Navigation -->
    <div class="tree">
        <ul>
            <!-- CONSTRUCTION -->
            <li>
                <label class="tree-item-label" onclick="toggleTree(this)">
                    <span class="tree-toggle"></span>
                    <i class="bi bi-hammer"></i>
                    <strong><?= trans('common.construction') ?></strong>
                </label>
                <ul class="tree-children">
                    <!-- TECHNICAL OFFICE -->
                    <li>
                        <label class="tree-item-label" onclick="toggleTree(this)">
                            <span class="tree-toggle"></span>
                            <i class="bi bi-briefcase"></i>
                            <strong><?= trans('common.technical_office') ?></strong>
                        </label>
                        <ul class="tree-children">
                            <li>
                                <a href="/projects" class="tree-leaf">
                                    <i class="bi bi-diagram-3"></i>
                                    <?= trans('common.projects') ?>
                                </a>
                            </li>
                            <li>
                                <a href="/firms" class="tree-leaf">
                                    <i class="bi bi-building"></i>
                                    <?= trans('common.companies') ?>
                                </a>
                            </li>
                            <li>
                                <a href="/contracts" class="tree-leaf">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <?= trans('common.contracts') ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="tree-leaf">
                                    <i class="bi bi-list-check"></i>
                                    <?= trans('common.boq') ?>
                                </a>
                            </li>
                            <li>
                                <label class="tree-item-label" onclick="toggleTree(this)">
                                    <span class="tree-toggle"></span>
                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                    <strong><?= trans('common.reports') ?></strong>
                                </label>
                                <ul class="tree-children">
                                    <li>
                                        <a href="#" class="tree-leaf">
                                            <i class="bi bi-calendar-day"></i>
                                            <?= trans('common.daily_reports') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="tree-leaf">
                                            <i class="bi bi-calendar-week"></i>
                                            <?= trans('common.weekly_reports') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="tree-leaf">
                                            <i class="bi bi-calendar-month"></i>
                                            <?= trans('common.monthly_reports') ?>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <label class="tree-item-label" onclick="toggleTree(this)">
                                    <span class="tree-toggle"></span>
                                    <i class="bi bi-cash-flow"></i>
                                    <strong><?= trans('common.progress_payment') ?></strong>
                                </label>
                                <ul class="tree-children">
                                    <li>
                                        <a href="#" class="tree-leaf">
                                            <i class="bi bi-graph-up"></i>
                                            <?= trans('common.progress_payment_report') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/tutanak" class="tree-leaf">
                                            <i class="bi bi-clipboard-check"></i>
                                            Tutanaklı İşler
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <label class="tree-item-label" onclick="toggleTree(this)">
                                    <span class="tree-toggle"></span>
                                    <i class="bi bi-piggy-bank"></i>
                                    <strong><?= trans('common.cost_control') ?></strong>
                                </label>
                                <ul class="tree-children">
                                    <li>
                                        <a href="/muhasebe" class="tree-leaf">
                                            <i class="bi bi-calculator"></i>
                                            <?= trans('common.accounting') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/tevkifat" class="tree-leaf">
                                            <i class="bi bi-receipt-cutoff"></i>
                                            <?= trans('common.tevkifat') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/bakiye" class="tree-leaf">
                                            <i class="bi bi-cash-coin"></i>
                                            Bakiye
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/costestimation" class="tree-leaf">
                                            <i class="bi bi-receipt"></i>
                                            <?= trans('common.cost_estimation') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/barter" class="tree-leaf">
                                            <i class="bi bi-shuffle"></i>
                                            <?= trans('common.barter') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/costcodes" class="tree-leaf">
                                            <i class="bi bi-code-slash"></i>
                                            <?= trans('common.cost_codes') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/reports" class="tree-leaf">
                                            <i class="bi bi-graph-up"></i>
                                            <?= trans('common.reports') ?>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <!-- PURCHASING -->
                    <li>
                        <label class="tree-item-label" onclick="toggleTree(this)">
                            <span class="tree-toggle"></span>
                            <i class="bi bi-cart"></i>
                            <strong>Purchasing</strong>
                        </label>
                        <ul class="tree-children">
                            <li>
                                <a href="#" class="tree-leaf">
                                    <i class="bi bi-bag"></i>
                                    Purchase Orders
                                </a>
                            </li>
                            <li>
                                <a href="#" class="tree-leaf">
                                    <i class="bi bi-truck"></i>
                                    Deliveries
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- HR -->
                    <li>
                        <label class="tree-item-label" onclick="toggleTree(this)">
                            <span class="tree-toggle"></span>
                            <i class="bi bi-people"></i>
                            <strong><?= trans('common.hr') ?></strong>
                        </label>
                        <ul class="tree-children">
                            <li>
                                <a href="/attendance/report" class="tree-leaf">
                                    <i class="bi bi-clock"></i>
                                    <?= trans('common.attendance') ?>
                                </a>
                            </li>
                            <li>
                                <a href="/users" class="tree-leaf">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <?= trans('common.users') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <!-- APPOINTMENT -->
            <li>
                <label class="tree-item-label" onclick="toggleTree(this)">
                    <span class="tree-toggle collapsed"></span>
                    <i class="bi bi-calendar-event"></i>
                    <strong>Appointment</strong>
                </label>
                <ul class="tree-children collapsed">
                    <li>
                        <a href="/appointments" class="tree-leaf">
                            <i class="bi bi-calendar2-check"></i>
                            Appointments
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<hr>

<script>
    function toggleTree(label) {
        const toggle = label.querySelector('.tree-toggle');
        const children = label.parentElement.querySelector('.tree-children');

        if (toggle && children) {
            toggle.classList.toggle('collapsed');
            children.classList.toggle('collapsed');
        }
    }
</script>