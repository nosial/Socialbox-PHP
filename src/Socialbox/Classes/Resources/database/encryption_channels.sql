create table encryption_channels
(
    uuid                            varchar(36)                                                                                 default uuid()              not null comment 'The Unique Universal Identifier of the encryption channel'
        primary key comment 'The Unique Index for the Encryption Channel UUID',
    status                          enum ('AWAITING_RECEIVER', 'SERVER_REJECTED', 'PEER_REJECTED', 'ERROR', 'OPENED', 'CLOSED') default 'AWAITING_RECEIVER' not null comment 'The status of the encryption channel',
    calling_peer_address            varchar(320)                                                                                                            not null comment 'The address of the calling peer for the encryption channel',
    calling_public_encryption_key   varchar(64)                                                                                                             not null comment 'The public encryption key of the caller used for dhe',
    receiving_peer_address          varchar(320)                                                                                                            not null comment 'The receiving peer of the the encryption channel',
    receiving_public_encryption_key varchar(64)                                                                                                             null comment 'The public encryption key of the receiver used for dhe',
    created                         timestamp                                                                                   default current_timestamp() not null comment 'The Timestamp for when this channel was created',
    constraint encryption_channels_uuid_uindex
        unique (uuid) comment 'The Unique Index for the Encryption Channel UUID'
)
    comment 'Table for housing end to end encryption channels for peers';

create index encryption_channels_calling_peer_address_index
    on encryption_channels (calling_peer_address)
    comment 'The index of the calling peer address';

create index encryption_channels_receiving_peer_address_index
    on encryption_channels (receiving_peer_address)
    comment 'The index of the receiving peer address';

create index encryption_channels_status_index
    on encryption_channels (status)
    comment 'The index of the encryption channel status';

