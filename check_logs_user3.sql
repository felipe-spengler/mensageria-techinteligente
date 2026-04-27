USE mensageria;
SELECT id, `to`, status, created_at, sent_at, updated_at 
FROM message_logs 
WHERE api_key_id IN (SELECT id FROM api_keys WHERE user_id = 3) 
AND created_at >= CURDATE() 
ORDER BY created_at DESC;
