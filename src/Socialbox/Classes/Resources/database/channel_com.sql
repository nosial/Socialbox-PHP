create table channel_com
(
    uuid         varchar(36) default uuid()              not null comment 'The UUID of the message',
    channel_uuid varchar(36)                             not null comment 'The UUID of the encryption channel used',
    recipient    enum ('SENDER', 'RECEIVER')             not null comment 'The recipient of the message',
    message      text                                    not null comment 'The encrypted message content',
    signature    varchar(64)                             not null comment 'The signature of the decrypted message',
    received     tinyint(1)  default 0                   not null comment 'True if the message was received by the recipient',
    timestamp    timestamp   default current_timestamp() not null comment 'The timestamp of the mssage being sent',
    primary key (uuid, channel_uuid) comment 'The Unique Pair Index for the channel UUID and message UUID',
    constraint channel_com_uuid_channel_uuid_uindex
        unique (uuid, channel_uuid) comment 'The Unique Pair Index for the channel UUID and message UUID',
    constraint channel_com_uuid_channel_uuid_uindex_2
        unique (uuid, channel_uuid) comment 'The Unique Index Pair for the channel UUID and message UUID',
    constraint channel_com_encryption_channels_uuid_fk
        foreign key (channel_uuid) references encryption_channels (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing communication messages over encryption channels';

create index channel_com_received_index
    on channel_com (received)
    comment 'The index for the received column';

create index channel_com_recipient_index
    on channel_com (recipient)
    comment 'The index for the recipient column';

create index channel_com_timestamp_index
    on channel_com (timestamp)
    comment 'The index for the Timestamp column';

