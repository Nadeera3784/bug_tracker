WITH pivot_data AS (
  SELECT 
    u.id AS user_id,
    u.email,
    p.name AS property_name,
    COALESCE(up.value_string, 
             CAST(up.value_int AS VARCHAR), 
             CAST(up.value_datetime AS VARCHAR)) AS property_value
  FROM users u
  CROSS JOIN properties p
  LEFT JOIN users_properties up ON u.id = up.user_id AND p.id = up.property_id
)
SELECT 
  pd.user_id,
  pd.email,
  MAX(CASE WHEN pd.property_name = 'name' THEN pd.property_value END) AS name,
  MAX(CASE WHEN pd.property_name = 'likes' THEN pd.property_value END) AS likes,
  MAX(CASE WHEN pd.property_name = 'dob' THEN pd.property_value END) AS dob
FROM pivot_data pd
GROUP BY pd.user_id, pd.email
ORDER BY pd.user_id;