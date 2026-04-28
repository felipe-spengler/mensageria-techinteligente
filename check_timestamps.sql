SELECT COUNT(*) as queued_total, MIN(updated_at) as oldest, MAX(updated_at) as newest 
FROM message_logs 
WHERE status='queued' 
AND api_key_id IN (SELECT id FROM api_keys WHERE user_id=3);
