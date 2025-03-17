DROP TABLE IF EXISTS encryption_channels_com;
CREATE TABLE encryption_channels_com
(
    uuid         varchar(36)                           DEFAULT uuid()              NOT NULL COMMENT 'The Unique Universal Identifier of the message for the encryption channel',
    channel_uuid varchar(36)                                                       NOT NULL COMMENT 'The UUID of the channel that the message belongs to',
    recipient    ENUM ('CALLER', 'RECEIVER')                                       NOT NULL COMMENT 'The recipient of the message',
    status       ENUM ('SENT', 'RECEIVED', 'REJECTED') DEFAULT 'SENT'              NOT NULL COMMENT 'The status of the message, SENT being the default, RECEIVED is when the recipient receives the message successfully and REJECTED is when the message cannot be decrypted, or the checksum failed.',
    checksum     varchar(64)                                                       NOT NULL COMMENT 'The SHA512 hash of the decrypted message contents',
    data         text                                                              NOT NULL COMMENT 'The data of the message',
    timestamp    timestamp                             DEFAULT current_timestamp() NOT NULL COMMENT 'The Timestamp of the message',
    PRIMARY KEY (uuid, channel_uuid) COMMENT 'The Unique Primary Index Pair for the channel_uuid and uuid of the message',
    CONSTRAINT encryption_channels_com_uuid_channel_uuid_uindex
        UNIQUE (uuid, channel_uuid) COMMENT 'The Unique Primary Index Pair for the channel_uuid and uuid of the message'
)
    COMMENT 'The table for housing communication messages sent over encryption channels';

CREATE INDEX encryption_channels_com_recipient_index
    ON encryption_channels_com (recipient)
    COMMENT 'The index of the recipient column used for indexing';

CREATE INDEX encryption_channels_com_timestamp_index
    ON encryption_channels_com (timestamp)
    COMMENT 'The index of the Timestamp column';

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_name = 'encryption_channels_com_encryption_channels_uuid_fk'
      AND table_name = 'encryption_channels_com'
);

SET @sql = IF(@constraint_exists = 0,
              'ALTER TABLE encryption_channels_com
               ADD CONSTRAINT encryption_channels_com_encryption_channels_uuid_fk
               FOREIGN KEY (channel_uuid) REFERENCES encryption_channels (uuid)
               ON UPDATE CASCADE ON DELETE CASCADE',
              'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;