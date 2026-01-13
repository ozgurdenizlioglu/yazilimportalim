<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>

<div style="margin-top: 30px;">

    <!-- Main Accordion -->
    <div class="accordion" id="mainAccordion">
        
        <!-- CONSTRUCTION Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#construction" aria-expanded="true" aria-controls="construction">
                    <i class="bi bi-hammer me-2"></i>CONSTRUCTION
                </button>
            </h2>
            <div id="construction" class="accordion-collapse collapse show" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-0">

                    <!-- Sub Accordion for Construction -->
                    <div class="accordion accordion-flush" id="constructionSubAccordion">
                        
                        <!-- TECHNICAL OFFICE -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalOffice" aria-expanded="false" aria-controls="technicalOffice">
                                    <i class="bi bi-briefcase me-2"></i>TECHNICAL OFFICE
                                </button>
                            </h3>
                            <div id="technicalOffice" class="accordion-collapse collapse" data-bs-parent="#constructionSubAccordion">
                                <div class="accordion-body p-2">
                                    <div class="list-group list-group-flush">
                                        <a href="/projects" class="list-group-item list-group-item-action">
                                            <i class="bi bi-diagram-3 me-2"></i>Projects
                                        </a>
                                        <a href="/firms" class="list-group-item list-group-item-action">
                                            <i class="bi bi-building me-2"></i>Companies
                                        </a>
                                        <a href="/contracts" class="list-group-item list-group-item-action">
                                            <i class="bi bi-file-earmark-text me-2"></i>Contracts
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PURCHASING -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#purchasing" aria-expanded="false" aria-controls="purchasing">
                                    <i class="bi bi-cart me-2"></i>PURCHASING
                                </button>
                            </h3>
                            <div id="purchasing" class="accordion-collapse collapse" data-bs-parent="#constructionSubAccordion">
                                <div class="accordion-body p-2">
                                    <div class="list-group list-group-flush">
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="bi bi-bag me-2"></i>Purchase Orders
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="bi bi-truck me-2"></i>Deliveries
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <!-- APPOINTMENT Section -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#appointment" aria-expanded="false" aria-controls="appointment">
                    <i class="bi bi-calendar-event me-2"></i>APPOINTMENT
                </button>
            </h2>
            <div id="appointment" class="accordion-collapse collapse" data-bs-parent="#mainAccordion">
                <div class="accordion-body p-2">
                    <div class="list-group list-group-flush">
                        <a href="/appointments" class="list-group-item list-group-item-action">
                            <i class="bi bi-calendar2-check me-2"></i>Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<hr>

<!-- Users Section (at bottom or separate) -->
<div style="margin-top: 20px;">
    <p><a href="/users" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people me-1"></i>Kullanıcılar sayfasına git</a></p>
</div>
