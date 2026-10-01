-- =========================================================================
-- Migration: cleanup_duplicate_staff_payments.sql
-- Açıklama: Kasa hareketi ve bordro maas_gelir_kesinti tablolarına aynı anda
--           çift kaydedilen mükerrer personel ödeme (kategori=7) kayıtlarının temizlenmesi.
-- Tarih: 2026-10-01
-- =========================================================================

DELETE mgk FROM `maas_gelir_kesinti` mgk
INNER JOIN `case_transactions` ct 
    ON ct.person_id = mgk.person_id 
    AND ct.amount = mgk.tutar 
    AND ct.case_id = mgk.case_id
    AND ABS(TIMESTAMPDIFF(SECOND, ct.created_at, mgk.created_at)) <= 3
WHERE ct.sub_type = 7 
  AND mgk.kategori = 7;
