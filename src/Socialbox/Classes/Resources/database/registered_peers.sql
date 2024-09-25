create table registered_peers
(
    uuid       varchar(36) default uuid()              not null comment 'The Primary index for the peer uuid'
        primary key,
    username   varchar(255)                            not null comment 'The Unique username associated with the peer',
    flags      text                                    null comment 'Comma seprted flags associated with the peer',
    registered timestamp   default current_timestamp() not null comment 'The Timestamp for when the peer was registered on the network',
    constraint registered_peers_pk_2
        unique (username) comment 'The unique username for the peer',
    constraint registered_peers_username_uindex
        unique (username) comment 'The unique username for the peer',
    constraint registered_peers_uuid_uindex
        unique (uuid) comment 'The Primary index for the peer uuid'
)
    comment 'Table for housing registered peers under this network';

create index registered_peers_registered_index
    on registered_peers (registered)
    comment 'The Index for the reigstered column of the peer';

