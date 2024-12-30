create table authentication_passwords
(
    peer_uuid          varchar(36)                           not null comment 'The Universal Unique Identifier for the peer that is associated with this password record'
        primary key comment 'The primary unique index of the peer uuid',
    iv                 mediumtext                            not null comment 'The Initial Vector of the password record',
    encrypted_password mediumtext                            not null comment 'The encrypted password data',
    encrypted_tag      mediumtext                            not null comment 'The encrypted tag of the password record',
    updated            timestamp default current_timestamp() not null comment 'The Timestamp for when this record was last updated',
    constraint authentication_passwords_peer_uuid_uindex
        unique (peer_uuid) comment 'The primary unique index of the peer uuid',
    constraint authentication_passwords_registered_peers_uuid_fk
        foreign key (peer_uuid) references registered_peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing authentication passwords for registered peers';

create index authentication_passwords_updated_index
    on authentication_passwords (updated)
    comment 'The index of the of the updated column of the record';

