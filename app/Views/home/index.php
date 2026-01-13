<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>

<div style="margin-top: 30px;">
  
  <!-- Technical Office Section -->
  <div class="accordion" id="mainAccordion">
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#technicalOffice" aria-expanded="true" aria-controls="technicalOffice">
          <i class="bi bi-briefcase me-2"></i>Technical Office
        </button>
      </h2>
      <div id="technicalOffice" class="accordion-collapse collapse show" data-bs-parent="#mainAccordion">
        <div class="accordion-body p-0">
          
          <!-- Construction Sub-Section -->
          <div class="accordion accordion-flush" id="constructionAccordion">
            <div class="accordion-item">
              <h3 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#construction" aria-expanded="false" aria-controls="construction">
                  <i class="bi bi-hammer me-2"></i>Construction
                </button>
              </h3>
              <div id="construction" class="accordion-collapse collapse" data-bs-parent="#constructionAccordion">
                <div class="accordion-body">
                  <div class="list-group list-group-flush">
                    <a href="/firms" class="list-group-item list-group-item-action">
                      <i class="bi bi-building me-2"></i>Firmalar sayfasına git
                    </a>
                    <a href="/projects" class="list-group-item list-group-item-action">
                      <i class="bi bi-diagram-3 me-2"></i>Projeler sayfasına git
                    </a>
                    <a href="/contracts" class="list-group-item list-group-item-action">
                      <i class="bi bi-file-earmark-text me-2"></i>Sozlesmeler sayfasına git
                    </a>
                    <a href="/appointments" class="list-group-item list-group-item-action">
                      <i class="bi bi-calendar-event me-2"></i>Appointments
                    </a>
                  </div>
                </div>
              </div>
            </div>
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