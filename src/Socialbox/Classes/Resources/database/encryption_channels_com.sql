create table encryption_channels_com
(
    uuid         varchar(36)                           default uuid()              not null comment 'The Unique Universal Identifier of the message for the encryption channel',
    channel_uuid varchar(36)                                                       not null comment 'The UUID of the channel that the message belongs to',
    recipient    enum ('CALLER', 'RECEIVER')                                       not null comment 'The recipient of the message',
    status       enum ('SENT', 'RECEIVED', 'REJECTED') default 'SENT'              not null comment 'The status of the message, SENT being the default, RECEIVED is when the recipient receives the message successfully and REJECTED is when the message cannot be decrypted, or the checksum failed.',
    checksum     varchar(64)                                                       not null comment 'The SHA512 hash of the decrypted message contents',
    data         text                                                              not null comment 'The data of the message',
    timestamp    timestamp                             default current_timestamp() not null comment 'The Timestamp of the message',
    primary key (uuid, channel_uuid) comment 'The Unique Primary Index Pair for the channel_uuid and uuid of the message',
    constraint encryption_channels_com_uuid_channel_uuid_uindex
        unique (uuid, channel_uuid) comment 'The Unique Primary Index Pair for the channel_uuid and uuid of the message',
    constraint encryption_channels_com_encryption_channels_uuid_fk
        foreign key (channel_uuid) references encryption_channels (uuid)
            on update cascade on delete cascade
)
    comment 'The table for housing communication messages sent over encryption channels';

create index encryption_channels_com_recipient_index
    on encryption_channels_com (recipient)
    comment 'The index of the recipient column used for indexing';

create index encryption_channels_com_timestamp_index
    on encryption_channels_com (timestamp)
    comment 'The index of the Timestamp column';

