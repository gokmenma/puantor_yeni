CREATE TABLE IF NOT EXISTS supports (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status TINYINT NOT NULL DEFAULT 0,
    program_name VARCHAR(50) NOT NULL DEFAULT 'puantor',
    user_last_read_message_id INT NOT NULL DEFAULT 0,
    admin_last_read_message_id INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_supports_user_program (user_id, program_name),
    KEY idx_supports_program_status (program_name, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS supports_message (
    id INT NOT NULL AUTO_INCREMENT,
    support_id INT NOT NULL,
    message TEXT NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_supports_message_support (support_id),
    CONSTRAINT fk_supports_message_support
        FOREIGN KEY (support_id) REFERENCES supports (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Merkezi tablodan yalnızca Puantor kayıtlarını, kimliklerini koruyarak bir kez kopyala.
INSERT IGNORE INTO supports (
    id,
    user_id,
    subject,
    message,
    status,
    program_name,
    user_last_read_message_id,
    admin_last_read_message_id,
    created_at
)
SELECT
    id,
    user_id,
    subject,
    message,
    status,
    program_name,
    user_last_read_message_id,
    admin_last_read_message_id,
    created_at
FROM mbeyazil_panel.supports
WHERE program_name = 'puantor';

INSERT IGNORE INTO supports_message (
    id,
    support_id,
    message,
    author,
    created_at
)
SELECT
    sm.id,
    sm.support_id,
    sm.message,
    sm.author,
    sm.created_at
FROM mbeyazil_panel.supports_message sm
INNER JOIN mbeyazil_panel.supports s ON s.id = sm.support_id
WHERE s.program_name = 'puantor';
