create table registered_peers
(
    uuid            varchar(36)  default uuid()              not null comment 'The Primary index for the peer uuid'
        primary key,
    username        varchar(255)                             not null comment 'The Unique username associated with the peer',
    server          varchar(255) default 'host'              not null comment 'The server that this peer is registered to',
    display_name    varchar(255)                             null comment 'Optional. The Non-Unique Display name of the peer',
    display_picture varchar(36)                              null comment 'The UUID of the display picture that is used, null if none is set.',
    flags           text                                     null comment 'Comma seprted flags associated with the peer',
    enabled         tinyint(1)   default 0                   not null comment 'Boolean column to indicate if this account is Enabled, by default it''s Disabled until the account is verified.',
    updated         timestamp    default current_timestamp() not null comment 'The Timestamp for when this record was last updated',
    created         timestamp    default current_timestamp() not null comment 'The Timestamp for when the peer was registered on the network',
    constraint registered_peers_server_username_uindex
        unique (server, username) comment 'The Unique Username + Server Index Pair',
    constraint registered_peers_uuid_uindex
        unique (uuid) comment 'The Primary index for the peer uuid'
)
    comment 'Table for housing registered peers under this network';

create index registered_peers_enabled_index
    on registered_peers (enabled)
    comment 'The index of the enabled column for registered peers';

create index registered_peers_registered_index
    on registered_peers (created)
    comment 'The Index for the reigstered column of the peer';

create index registered_peers_server_index
    on registered_peers (server)
    comment 'The Index for the peer''s server';

create index registered_peers_updated_index
    on registered_peers (updated)
    comment 'The Index for the update column';

create index registered_peers_username_index
    on registered_peers (username)
    comment 'The index for the registered username';

