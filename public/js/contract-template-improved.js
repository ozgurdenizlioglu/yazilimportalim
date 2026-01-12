// Improved contract template download with multiple sheets
async function loadXlsxIfNeeded() {
    // XLSX is already loaded at the top of the page via CDN
    // Just wait for it to be available
    const timeout = 10000;
    const start = Date.now();

    while (!window.XLSX && (Date.now() - start) < timeout) {
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    if (!window.XLSX) {
        throw new Error('XLSX kütüphanesi yüklenemedi. Lütfen sayfayı yenileyin.');
    }
}

async function downloadTemplateXlsxImproved() {
    console.log('downloadTemplateXlsx called');

    try {
        await loadXlsxIfNeeded();
    } catch (e) {
        alert('XLSX kütüphanesi yüklenemedi: ' + (e?.message || e));
        return;
    }

    try {
        // Fetch template data from backend (projects, disciplines, etc.)
        console.log('Fetching /contracts/template-data...');
        const response = await fetch('/contracts/template-data');
        console.log('Response status:', response.status, response.ok);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText || 'Template verisi alınamadı'}`);
        }

        const templateData = await response.json();
        console.log('Template data received:', templateData);

        // Check if response contains error
        if (!templateData.ok) {
            console.error('API Error:', templateData.error);
            throw new Error(templateData.error || 'Template verisi alınamadı');
        }

        // Define the header row for data sheet
        const dataHeaders = [
            'subcontractor_company_name',
            'contract_date',
            'end_date',
            'subject',
            'project_name',
            'discipline_name',
            'branch_name',
            'amount',
            'currency_code'
        ];

        const sample1 = [
            'Yade Mühendislik Sanayi Ticaret Ltd. Şti.',
            '2026-01-02',
            '2026-12-31',
            'Yazılım Geliştirme',
            'Portal Projesi',
            '',
            '',
            '100000.00',
            'TRY'
        ];

        const sample2 = [
            'SİMETRİ MERDİVEN DEKORASYON İNŞ. OTO. TUR. SAN. VE TİC. LTD. ŞTİ.',
            '2026-02-01',
            '2026-06-30',
            'Yazılım Bakımı',
            'Mobil Uygulama',
            '',
            '',
            '50000.00',
            'USD'
        ];

        const dataRows = [dataHeaders, sample1, sample2];
        const wsData = XLSX.utils.aoa_to_sheet(dataRows);
        wsData['!cols'] = dataHeaders.map(h => ({
            wch: Math.min(Math.max(String(h).length + 2, 15), 25)
        }));

        // Sheet 2: Projects reference
        const projectsData = [['ID', 'Proje Adı', 'Kısa Adı']];

        if (templateData.projects && Array.isArray(templateData.projects)) {
            templateData.projects.forEach(p => {
                projectsData.push([p.id, p.name, p.short_name || '']);
            });
        }

        const wsProjects = XLSX.utils.aoa_to_sheet(projectsData);
        wsProjects['!cols'] = [{ wch: 8 }, { wch: 30 }, { wch: 15 }];

        // Sheet 2b: Companies reference
        const companiesData = [['ID', 'Şirket Adı']];

        if (templateData.companies && Array.isArray(templateData.companies)) {
            templateData.companies.forEach(c => {
                companiesData.push([c.id, c.name]);
            });
        }

        const wsCompanies = XLSX.utils.aoa_to_sheet(companiesData);
        wsCompanies['!cols'] = [{ wch: 8 }, { wch: 50 }];

        // Sheet 3: Disciplines & Branches reference
        const discData = [['Disiplin ID', 'Disiplin Adı', 'Alt Dal ID', 'Alt Dal Adı']];

        if (templateData.disciplines && Array.isArray(templateData.disciplines)) {
            templateData.disciplines.forEach(d => {
                discData.push([
                    d.discipline_id,
                    d.discipline_name,
                    d.branch_id || '',
                    d.branch_name || ''
                ]);
            });
        }

        const wsDisc = XLSX.utils.aoa_to_sheet(discData);
        wsDisc['!cols'] = [{ wch: 12 }, { wch: 25 }, { wch: 12 }, { wch: 30 }];

        // Sheet 4: Instructions
        const instructionsData = [
            ['SÖZLEŞME ŞABLONU - KULLANIM KILAVUZU'],
            [],
            ['ADIMLAR:'],
            ['1. Data sekmesine sözleşme bilgilerini girin'],
            ['2. Zorunlu alanlar: subcontractor_company_name, contract_date, subject, project_name'],
            ['3. Şirket adı için Şirketler sekmesine bakın ve tam adını yazın'],
            ['4. Proje adı için Projeler sekmesine bakın ve tam adını yazın'],
            ['5. Disiplin/Alt dal adı için Disiplinler & Alt Dallar sekmesine bakın ve tam adını yazın'],
            ['6. contract_title otomatik olarak oluşturulur (SZL_ formatında)'],
            [],
            ['ALAN AÇIKLAMALARI:'],
            ['subcontractor_company_name', 'Yüklenici şirket adı (Şirketler sekmesine bakın)'],
            ['contract_date', 'Sözleşme tarihi (YYYY-MM-DD formatında)'],
            ['end_date', 'Bitiş tarihi (opsiyonel, YYYY-MM-DD formatında)'],
            ['subject', 'Sözleşme konusu'],
            ['project_name', 'Proje adı (Projeler sekmesine bakın, "Proje Adı" sütunu)'],
            ['discipline_name', 'Disiplin adı (Disiplinler & Alt Dallar sekmesine bakın, "Disiplin Adı" sütunu, opsiyonel)'],
            ['branch_name', 'Alt dal adı (Disiplinler & Alt Dallar sekmesine bakın, "Alt Dal Adı" sütunu, opsiyonel)'],
            ['amount', 'Sözleşme tutarı (örn: 100000.00, opsiyonel - varsayılan: 0)'],
            ['currency_code', 'Para birimi kodu (TRY, USD, EUR - varsayılan: TRY)'],
            [],
            ['NOTLAR:'],
            ['• Data sekmesinde başlık satırını değiştirmeyin'],
            ['• contract_title otomatik olarak oluşturulur, sütundan kaldırıldı'],
            ['• Tarihler mutlaka YYYY-MM-DD formatında olmalıdır'],
            ['• Proje/Disiplin/Alt dal adlarını tam olarak yazın (case-sensitive değil)'],
            ['• Tüm zorunlu alanları doldurunuz'],
            ['• Dosya adını değiştirip kaydedebilirsiniz']
        ];

        const wsInstructions = XLSX.utils.aoa_to_sheet(instructionsData);
        wsInstructions['!cols'] = [{ wch: 35 }, { wch: 50 }];

        // Create workbook with all sheets
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, wsData, 'Data');
        XLSX.utils.book_append_sheet(wb, wsCompanies, 'Şirketler');
        XLSX.utils.book_append_sheet(wb, wsProjects, 'Projeler');
        XLSX.utils.book_append_sheet(wb, wsDisc, 'Disiplinler & Alt Dallar');
        XLSX.utils.book_append_sheet(wb, wsInstructions, 'Talimatlar');

        XLSX.writeFile(wb, 'sozlesme_sablonu_' + new Date().toISOString().split('T')[0] + '.xlsx', {
            compression: true
        });

        console.log('Template downloaded successfully');
    } catch (e) {
        console.error('Template download error:', e);
        alert('Şablon indirme hatası: ' + (e?.message || e));
    }
}

// Call this function instead of downloadTemplateXlsx in your event listeners
// document.getElementById('downloadTemplate').addEventListener('click', downloadTemplateXlsxImproved);
