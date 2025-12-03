-- Extensions (uuid generation)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 1) companies tablosu
CREATE TABLE IF NOT EXISTS public.companies
(
    id               BIGSERIAL PRIMARY KEY,
    uuid             uuid NOT NULL DEFAULT gen_random_uuid(),
    
    -- Kimlik
    name             varchar(200) NOT NULL,
    short_name       varchar(100),
    legal_type       varchar(30),                  -- ör: 'limited','anonim','sole_prop','ngo','other'
    registration_no  varchar(100),                 -- ticaret sicil no
    mersis_no        varchar(50),                  -- TR spesifik
    tax_office       varchar(120),
    tax_number       varchar(50),

    -- İletişim
    email            text,
    phone            varchar(30),
    secondary_phone  varchar(30),
    fax              varchar(30),
    website          text,

    -- Adres
    address_line1    varchar(200),
    address_line2    varchar(200),
    city             varchar(100),
    state_region     varchar(100),
    postal_code      varchar(20),
    country_code     char(2),
    latitude         numeric(9,6),                 -- opsiyonel
    longitude        numeric(9,6),                 -- opsiyonel

    -- Operasyonel
    industry         varchar(120),
    status           varchar(20) DEFAULT 'active', -- 'active','prospect','lead','suspended','inactive'
    currency_code    char(3),                      -- ISO-4217
    timezone         varchar(50),
    vat_exempt       boolean NOT NULL DEFAULT false,
    e_invoice_enabled boolean NOT NULL DEFAULT false,

    -- Medya ve Not
    logo_url         text,                         -- dosya depolama url/key
    notes            text,

    -- Durum/Audit
    is_active        boolean NOT NULL DEFAULT true,
    created_at       timestamptz NOT NULL DEFAULT now(),
    updated_at       timestamptz NOT NULL DEFAULT now(),
    deleted_at       timestamptz,

    created_by       bigint,
    updated_by       bigint,

    -- Benzersizlikler ve kontroller
    CONSTRAINT companies_uuid_key UNIQUE (uuid),
    CONSTRAINT companies_tax_number_key UNIQUE (tax_number),
    CONSTRAINT companies_registration_no_key UNIQUE (registration_no),
    CONSTRAINT companies_mersis_no_key UNIQUE (mersis_no),
    CONSTRAINT companies_status_check CHECK (status IN ('active','prospect','lead','suspended','inactive'))
);

-- FK'ler (users tablosuna)
ALTER TABLE public.companies
    ADD CONSTRAINT companies_created_by_fkey
        FOREIGN KEY (created_by) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE public.companies
    ADD CONSTRAINT companies_updated_by_fkey
        FOREIGN KEY (updated_by) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE SET NULL;

-- Kullanışlı indexler
CREATE INDEX IF NOT EXISTS idx_companies_name_trgm ON public.companies USING gin (name gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_companies_short_name_trgm ON public.companies USING gin (short_name gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_companies_tax_office ON public.companies (tax_office);
CREATE INDEX IF NOT EXISTS idx_companies_status ON public.companies (status);
CREATE INDEX IF NOT EXISTS idx_companies_country_city ON public.companies (country_code, city);
CREATE INDEX IF NOT EXISTS idx_companies_deleted_at ON public.companies (deleted_at);

-- Not: trigram index için gerekli uzantı
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- 2) Şirket Sorumluları (çoklu seçilebilir kullanıcılar)
CREATE TABLE IF NOT EXISTS public.company_responsibles
(
    company_id   bigint NOT NULL,
    user_id      bigint NOT NULL,
    role         varchar(50),          -- ör: 'owner','manager','accountant','sales','technical'
    is_primary   boolean NOT NULL DEFAULT false,
    sort_order   integer,              -- UI sıralama
    notes        text,

    created_at   timestamptz NOT NULL DEFAULT now(),
    created_by   bigint,
    PRIMARY KEY (company_id, user_id),

    CONSTRAINT company_responsibles_company_fkey
        FOREIGN KEY (company_id) REFERENCES public.companies(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT company_responsibles_user_fkey
        FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT company_responsibles_created_by_fkey
        FOREIGN KEY (created_by) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_company_responsibles_role ON public.company_responsibles (role);
CREATE INDEX IF NOT EXISTS idx_company_responsibles_is_primary ON public.company_responsibles (is_primary);

-- 3) Güncelleme zamanını otomatik güncelleyen trigger (opsiyonel ama önerilir)
CREATE OR REPLACE FUNCTION public.set_updated_at()
RETURNS trigger AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_companies_set_updated_at ON public.companies;
CREATE TRIGGER trg_companies_set_updated_at
BEFORE UPDATE ON public.companies
FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- 4) Soft delete yardımcı görünümü (opsiyonel)
CREATE OR REPLACE VIEW public.active_companies AS
SELECT *
FROM public.companies
WHERE deleted_at IS NULL AND is_active = true;