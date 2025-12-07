ALTER TABLE public.attendance

ADD COLUMN photo_path text,

ADD COLUMN device_user_agent text,

ADD COLUMN device_platform text,

ADD COLUMN device_language text,

ADD COLUMN location_status text,

ADD COLUMN location_lat double precision,

ADD COLUMN location_lng double precision,

ADD COLUMN location_accuracy double precision;