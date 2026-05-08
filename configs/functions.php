<?php 

class DateFormat
{
    public static function dmY($date)
    {
        if (empty($date)) {
            return '';
        }
        return date('d.m.Y', strtotime($date));
    }

    public static function Ymd($date)
    {
        if (empty($date)) {
            return '';
        }
        return date('Y-m-d', strtotime($date));
    }
}
function alertdanger($message)
{
    echo '<div class="alert alert-important alert-danger alert-dismissible">
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <strong>Hata!</strong> ' . $message . '
  </div>';
}