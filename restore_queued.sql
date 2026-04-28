UPDATE message_logs 
SET status='queued', error_message=NULL, updated_at=NOW()
WHERE status='cancelled' 
  AND error_message LIKE '%deduplicação%'
  AND api_key_id IN (SELECT id FROM api_keys WHERE user_id = 3);

SELECT COUNT(*) as total_queued_agora
FROM message_logs 
WHERE status='queued' 
  AND api_key_id IN (SELECT id FROM api_keys WHERE user_id = 3);
