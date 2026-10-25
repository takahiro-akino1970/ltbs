CREATE TABLE customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_code VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    deleted_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_code (customer_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carriers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    carrier_code VARCHAR(50) NOT NULL,
    name VARCHAR(200) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    deleted_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carriers_code (carrier_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_carriers (
    customer_id BIGINT UNSIGNED NOT NULL,
    carrier_id BIGINT UNSIGNED NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (customer_id, carrier_id),
    CONSTRAINT fk_customer_carriers_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_customer_carriers_carrier
        FOREIGN KEY (carrier_id) REFERENCES carriers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NULL,
    carrier_id BIGINT UNSIGNED NULL,
    login_id VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(254) NULL,
    tel VARCHAR(30) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    last_login_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    deleted_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_login_id (login_id),
    KEY ix_users_customer_status (customer_id, status),
    KEY ix_users_carrier_status (carrier_id, status),
    CONSTRAINT fk_users_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_users_carrier
        FOREIGN KEY (carrier_id) REFERENCES carriers (id),
    CONSTRAINT chk_users_one_organization CHECK (
        (customer_id IS NOT NULL AND carrier_id IS NULL)
        OR (customer_id IS NULL AND carrier_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    organization_type VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicle_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_vehicle_types_code (code),
    KEY ix_vehicle_types_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_statuses (
    code VARCHAR(30) NOT NULL,
    name VARCHAR(50) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_terminal TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transport_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_no VARCHAR(32) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    requester_user_id BIGINT UNSIGNED NOT NULL,
    vehicle_type_id BIGINT UNSIGNED NOT NULL,
    vehicle_type_name_snapshot VARCHAR(100) NOT NULL,
    status_code VARCHAR(30) NOT NULL,
    chassis_number VARCHAR(100) NULL,
    vehicle_number VARCHAR(30) NULL,
    requested_at DATETIME(3) NOT NULL,
    departure_scheduled_at DATETIME(3) NULL,
    arrival_scheduled_at DATETIME(3) NULL,
    actual_departed_at DATETIME(3) NULL,
    actual_arrived_at DATETIME(3) NULL,
    remarks TEXT NULL,
    accepted_carrier_id BIGINT UNSIGNED NULL,
    accepted_by BIGINT UNSIGNED NULL,
    accepted_at DATETIME(3) NULL,
    completed_at DATETIME(3) NULL,
    canceled_by BIGINT UNSIGNED NULL,
    canceled_at DATETIME(3) NULL,
    cancel_reason TEXT NULL,
    route_distance_m INT UNSIGNED NULL,
    route_duration_sec INT UNSIGNED NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_transport_orders_order_no (order_no),
    UNIQUE KEY uq_transport_orders_id_customer (id, customer_id),
    KEY ix_orders_customer_created (customer_id, created_at),
    KEY ix_orders_status_created (status_code, created_at),
    KEY ix_orders_requester_created (requester_user_id, created_at),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_orders_requester
        FOREIGN KEY (requester_user_id) REFERENCES users (id),
    CONSTRAINT fk_orders_vehicle_type
        FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types (id),
    CONSTRAINT fk_orders_status
        FOREIGN KEY (status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_orders_accepted_by
        FOREIGN KEY (accepted_by) REFERENCES users (id),
    CONSTRAINT fk_orders_canceled_by
        FOREIGN KEY (canceled_by) REFERENCES users (id),
    CONSTRAINT fk_orders_accepted_customer_carrier
        FOREIGN KEY (customer_id, accepted_carrier_id)
        REFERENCES customer_carriers (customer_id, carrier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    location_type VARCHAR(20) NOT NULL,
    name VARCHAR(200) NOT NULL,
    postal_code VARCHAR(10) NULL,
    prefecture VARCHAR(20) NULL,
    city VARCHAR(100) NULL,
    address_line VARCHAR(300) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    place_id VARCHAR(255) NULL,
    contact_name VARCHAR(200) NULL,
    contact_tel VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_customer_locations_customer_type
        (customer_id, location_type, is_active),
    CONSTRAINT fk_customer_locations_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_points (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    point_type VARCHAR(20) NOT NULL,
    customer_location_id BIGINT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    postal_code VARCHAR(10) NULL,
    prefecture VARCHAR(20) NULL,
    city VARCHAR(100) NULL,
    address_line VARCHAR(300) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    place_id VARCHAR(255) NULL,
    provider_address TEXT NULL,
    source VARCHAR(20) NOT NULL,
    is_manually_edited TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_at DATETIME(3) NULL,
    contact_name VARCHAR(200) NULL,
    contact_tel VARCHAR(30) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_points_order_type (order_id, point_type),
    CONSTRAINT fk_order_points_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_points_location
        FOREIGN KEY (customer_location_id) REFERENCES customer_locations (id),
    CONSTRAINT chk_order_points_type
        CHECK (point_type IN ('PICKUP', 'DELIVERY')),
    CONSTRAINT chk_order_points_source
        CHECK (source IN ('MAP', 'HISTORY', 'MANUAL'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_carrier_targets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    carrier_id BIGINT UNSIGNED NOT NULL,
    response_status VARCHAR(20) NOT NULL DEFAULT 'OFFERED',
    offered_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    responded_at DATETIME(3) NULL,
    responded_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_carrier_targets_pair (order_id, carrier_id),
    KEY ix_order_carrier_targets_queue
        (carrier_id, response_status, offered_at),
    CONSTRAINT fk_order_carrier_targets_order_customer
        FOREIGN KEY (order_id, customer_id)
        REFERENCES transport_orders (id, customer_id),
    CONSTRAINT fk_order_carrier_targets_customer_carrier
        FOREIGN KEY (customer_id, carrier_id)
        REFERENCES customer_carriers (customer_id, carrier_id),
    CONSTRAINT fk_order_carrier_targets_responded_by
        FOREIGN KEY (responded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    from_status_code VARCHAR(30) NULL,
    to_status_code VARCHAR(30) NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    comment TEXT NULL,
    PRIMARY KEY (id),
    KEY ix_order_status_histories_order_time (order_id, changed_at),
    CONSTRAINT fk_order_status_histories_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_status_histories_from
        FOREIGN KEY (from_status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_order_status_histories_to
        FOREIGN KEY (to_status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_order_status_histories_user
        FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED NOT NULL,
    message_type VARCHAR(30) NOT NULL DEFAULT 'MESSAGE',
    body TEXT NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_order_messages_order_created (order_id, created_at),
    CONSTRAINT fk_order_messages_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_messages_sender
        FOREIGN KEY (sender_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    content_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    deleted_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    KEY ix_order_attachments_order (order_id),
    CONSTRAINT fk_order_attachments_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_attachments_user
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ocr_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attachment_id BIGINT UNSIGNED NOT NULL,
    candidate_vehicle_number VARCHAR(30) NULL,
    confidence DECIMAL(5,4) NULL,
    result_status VARCHAR(20) NOT NULL,
    confirmed_vehicle_number VARCHAR(30) NULL,
    confirmed_by BIGINT UNSIGNED NULL,
    confirmed_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_ocr_results_attachment (attachment_id, created_at),
    CONSTRAINT fk_ocr_results_attachment
        FOREIGN KEY (attachment_id) REFERENCES order_attachments (id),
    CONSTRAINT fk_ocr_results_confirmed_by
        FOREIGN KEY (confirmed_by) REFERENCES users (id),
    CONSTRAINT chk_ocr_results_confidence
        CHECK (confidence IS NULL OR confidence BETWEEN 0 AND 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    payload JSON NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    processed_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    KEY ix_notification_events_queue (status, created_at),
    CONSTRAINT fk_notification_events_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_notification_events_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id BIGINT UNSIGNED NOT NULL,
    recipient_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    read_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_user_notifications_inbox
        (recipient_user_id, read_at, created_at),
    CONSTRAINT fk_user_notifications_event
        FOREIGN KEY (event_id) REFERENCES notification_events (id),
    CONSTRAINT fk_user_notifications_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    endpoint_hash BINARY(32) NOT NULL,
    endpoint_ciphertext TEXT NOT NULL,
    p256dh_ciphertext TEXT NOT NULL,
    auth_ciphertext TEXT NOT NULL,
    browser_info VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    subscribed_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    last_used_at DATETIME(3) NULL,
    unsubscribed_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_subscriptions_endpoint_hash (endpoint_hash),
    KEY ix_push_subscriptions_user_status (user_id, status),
    CONSTRAINT fk_push_subscriptions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    notification_id BIGINT UNSIGNED NOT NULL,
    channel VARCHAR(20) NOT NULL,
    push_subscription_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME(3) NULL,
    failure_reason TEXT NULL,
    delivered_at DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_notification_deliveries_retry (status, last_attempt_at),
    CONSTRAINT fk_notification_deliveries_notification
        FOREIGN KEY (notification_id) REFERENCES user_notifications (id),
    CONSTRAINT fk_notification_deliveries_subscription
        FOREIGN KEY (push_subscription_id) REFERENCES push_subscriptions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY ix_audit_logs_entity (entity_type, entity_id, created_at),
    KEY ix_audit_logs_actor (actor_user_id, created_at),
    CONSTRAINT fk_audit_logs_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
