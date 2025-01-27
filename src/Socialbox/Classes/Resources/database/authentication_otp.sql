create table authentication_passwords
(
    peer_uuid varchar(36)                           not null comment 'The primary unique index of the peer uuid'
        primary key,
    hash      mediumtext                            not null comment 'The encrypted hash of the password',
    updated   timestamp default current_timestamp() not null comment 'The Timestamp for when this record was last updated',
    constraint authentication_passwords_peer_uuid_uindex
        unique (peer_uuid) comment 'The primary unique index of the peer uuid',
    constraint authentication_passwords_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing authentication passwords for registered peers';

create index authentication_passwords_updated_index
    on authentication_passwords (updated)
    comment 'The index of the of the updated column of the record';

