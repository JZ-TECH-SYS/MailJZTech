DELETE FROM emails_logs 
WHERE data_log < DATE_SUB(NOW(), INTERVAL :dias DAY)
