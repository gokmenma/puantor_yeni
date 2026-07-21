-- "Yillik Izin" (menu id 129) ve "Izin Talepleri" (menu id 130) menu satirlari
-- su ana kadar is_authorize=0 oldugu icin herkese aciliyordu (masaustunde), oysa
-- mobilde gercek yetki kontrolu yapiliyordu. Once role_auths backfill edildi
-- (bkz. backfill_yillik_izin_role_auths.sql), simdi menude de gercek kontrol
-- devreye alinabilir.

UPDATE menu SET is_authorize = 1 WHERE id IN (129, 130);
