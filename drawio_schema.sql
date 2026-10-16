CREATE TABLE employee (
  id BIGINT,
  npp CHARACTER VARYING(255),
  name CHARACTER VARYING(255),
  is_active INTEGER,
  email CHARACTER VARYING(255),
  last_login TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by_id INTEGER,
  updated_by_id INTEGER,
  PRIMARY KEY (id)
);

CREATE TABLE menu_access_logs (
  id BIGINT,
  user_id BIGINT,
  unit_id BIGINT,
  unit_name CHARACTER VARYING(255),
  menu_name CHARACTER VARYING(255),
  menu_title CHARACTER VARYING(255),
  accessed_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE lookup (
  id BIGINT,
  type CHARACTER VARYING(255),
  name CHARACTER VARYING(255),
  value INTEGER,
  order_no INTEGER,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by_id INTEGER,
  updated_by_id INTEGER,
  PRIMARY KEY (id)
);

CREATE TABLE news (
  id BIGINT,
  title TEXT,
  keyword TEXT,
  category CHARACTER VARYING(255),
  start_time DATE,
  end_time DATE,
  headline CHARACTER VARYING(255),
  content TEXT,
  attachment CHARACTER VARYING(255),
  target_unit TEXT,
  created_by CHARACTER VARYING(255),
  updated_by CHARACTER VARYING(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  attachments TEXT,
  PRIMARY KEY (id)
);

CREATE TABLE personal_access_tokens (
  id BIGINT,
  tokenable_type CHARACTER VARYING(255),
  tokenable_id BIGINT,
  name TEXT,
  token CHARACTER VARYING(64),
  abilities TEXT,
  last_used_at TIMESTAMP,
  expires_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE portal_settings (
  id BIGINT,
  section CHARACTER VARYING(255),
  category CHARACTER VARYING(255),
  title CHARACTER VARYING(255),
  url CHARACTER VARYING(255),
  image_name CHARACTER VARYING(255),
  order_no INTEGER,
  is_active BOOLEAN,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  target_unit JSON,
  parent_id BIGINT,
  PRIMARY KEY (id)
);

CREATE TABLE sessions (
  id BIGINT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE activity_logs (
  id BIGINT,
  user_id BIGINT,
  username CHARACTER VARYING(255),
  action CHARACTER VARYING(255),
  module CHARACTER VARYING(255),
  description TEXT,
  payload JSON,
  ip_address CHARACTER VARYING(255),
  user_agent TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE system_parameter (
  id BIGINT,
  key CHARACTER VARYING(255),
  value CHARACTER VARYING(255),
  title CHARACTER VARYING(255),
  url CHARACTER VARYING(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE system_error_logs (
  id BIGINT,
  user_id BIGINT,
  username CHARACTER VARYING(255),
  error_class CHARACTER VARYING(255),
  error_message TEXT,
  file CHARACTER VARYING(255),
  line INTEGER,
  trace TEXT,
  explanation TEXT,
  suggestion TEXT,
  url CHARACTER VARYING(255),
  method CHARACTER VARYING(8),
  ip_address CHARACTER VARYING(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  status CHARACTER VARYING(255),
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE user_login_logs (
  id BIGINT,
  user_id BIGINT,
  unit_id BIGINT,
  unit_name CHARACTER VARYING(255),
  login_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE users (
  id BIGINT,
  username CHARACTER VARYING(255),
  password CHARACTER VARYING(255),
  employee_id BIGINT,
  last_login TIMESTAMP,
  attempt_count INTEGER,
  last_logout TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by_id INTEGER,
  updated_by_id INTEGER,
  PRIMARY KEY (id),
  FOREIGN KEY (employee_id) REFERENCES employee(id)
);

CREATE TABLE employee_role (
  id BIGINT,
  employee_id BIGINT,
  unit_id BIGINT,
  role_id INTEGER,
  status_id INTEGER,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (employee_id) REFERENCES employee(id),
  FOREIGN KEY (unit_id) REFERENCES unit(id)
);

CREATE TABLE unit (
  id BIGINT,
  parent_id INTEGER,
  type INTEGER,
  code CHARACTER VARYING(255),
  short_name CHARACTER VARYING(255),
  name CHARACTER VARYING(255),
  address CHARACTER VARYING(255),
  is_active INTEGER,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  created_by_id INTEGER,
  updated_by_id INTEGER,
  PRIMARY KEY (id)
);

CREATE TABLE backup_histories (
  id BIGINT,
  datetime TIMESTAMP,
  description TEXT,
  status CHARACTER VARYING(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE backup_schedules (
  id BIGINT,
  frequency CHARACTER VARYING(255),
  custom_day CHARACTER VARYING(255),
  custom_hour CHARACTER VARYING(255),
  custom_minute CHARACTER VARYING(255),
  custom_second CHARACTER VARYING(255),
  is_active BOOLEAN,
  last_run_at TIMESTAMP,
  next_run_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  custom_datetime TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE user_session (
  id CHARACTER VARYING(255),
  user_id BIGINT,
  info CHARACTER VARYING(255),
  role_id INTEGER,
  unit_id INTEGER,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  payload TEXT,
  last_activity INTEGER,
  ip_address CHARACTER VARYING(45),
  user_agent TEXT,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

