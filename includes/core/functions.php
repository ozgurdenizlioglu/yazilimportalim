
<?php
function getProjects() {
    return [
        'kursiyer_takip' => 'Kursiyer Takip Programı',
        'hakedis_takip'  => 'Hakediş Takip Aracı',
    ];
}

function base_url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function project_path($name) {
    return PROJECTS_PATH . $name . '/module.php';
}