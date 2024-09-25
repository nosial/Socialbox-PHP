create table password_authentication
(
    peer_uuid varchar(36)                           not null comment 'The Primary unique Index for the peer UUID'
        primary key,
    value     varchar(128)                          not null comment 'The hash value of the pasword',
    updated   timestamp default current_timestamp() not null comment 'The Timestamp for when this record was last updated',
    constraint password_authentication_peer_uuid_uindex
        unique (peer_uuid) comment 'The Primary unique Index for the peer UUID',
    constraint password_authentication_registered_peers_uuid_fk
        foreign key (peer_uuid) references registered_peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing password authentications associated with peers';

create index password_authentication_updated_index
    on password_authentication (updated)
    comment 'The Indefor the updated timestamp';

