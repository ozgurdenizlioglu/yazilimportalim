<?php ?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Yeni Disiplin Oluştur</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/disciplines/store">
                <?php include '_form.php'; ?>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Oluştur
                    </button>
                    <a href="/disciplines" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Geri
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>