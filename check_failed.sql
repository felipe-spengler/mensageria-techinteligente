USE mensageria;
SELECT id, error_message, created_at FROM message_logs WHERE status = 'failed';
