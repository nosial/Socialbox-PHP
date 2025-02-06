create table encryption_channels
(
    uuid                            varchar(36)                                                                                                     not null comment 'The Unique Universal Identifier for the encryption channel'
        primary key comment 'The Unique Index of the encryption channel UUID',
    calling_peer                    varchar(320)                                                                                                    not null comment 'The address of the calling peer',
    calling_signature_uuid          varchar(64)                                                                                                     not null comment 'The UUID of the signing key that the calling peer is going to use to sign their messages',
    calling_signature_public_key    varchar(32)                                                                                                     not null,
    calling_encryption_public_key   varchar(32)                                                                                                     not null comment 'The public encryption key of the caller',
    receiving_peer                  varchar(320)                                                                                                    not null comment 'The address of the receiving peer',
    receiving_signature_uuid        varchar(256)                                                                                                    null comment 'The UUID of the signature that the receiver peer will use to sign messages with',
    receiving_signature_public_key  varchar(32)                                                                                                     null comment 'The public key of the receiver''s signing key',
    receiving_encryption_public_key varchar(32)                                                                                                     null comment 'The encryption key of the receiver',
    transport_encryption_algorithm  enum ('xchacha20', 'chacha20', 'aes256gcm')                                         default 'xchacha20'         not null comment 'The transport encryption algorithm used as selected by the caller',
    transport_encryption_key        varchar(256)                                                                                                    null comment 'The transport encryption key encrypted using the caller''s public encryption key',
    state                           enum ('AWAITING_RECEIVER', 'ERROR', 'DECLINED', 'AWAITING_DHE', 'OPENED', 'CLOSED') default 'AWAITING_RECEIVER' not null comment 'The current state of the encryption channel',
    created                         timestamp                                                                           default current_timestamp() not null comment 'The Timestamp for when this record was created',
    constraint encryption_channels_uuid_uindex
        unique (uuid) comment 'The Unique Index of the encryption channel UUID'
);

create index encryption_channels_calling_peer_index
    on encryption_channels (calling_peer)
    comment 'The index of the calling peer address';

create index encryption_channels_created_index
    on encryption_channels (created)
    comment 'The Index for when the record was created';

create index encryption_channels_receiving_peer_index
    on encryption_channels (receiving_peer)
    comment 'The index of the receiving peer address';

create index encryption_channels_state_index
    on encryption_channels (state)
    comment 'The index for the state column';

