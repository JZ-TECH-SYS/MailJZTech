SELECT * FROM emails_logs 
WHERE idsistema = :idsistema 
AND DATE(data_log) BETWEEN ':data_inicio' AND ':data_fim'
ORDER BY data_log DESC
