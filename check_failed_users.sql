USE mensageria;
SELECT ml.id, ak.user_id 
FROM message_logs ml 
JOIN api_keys ak ON ml.api_key_id = ak.id 
WHERE ml.status = 'failed';
