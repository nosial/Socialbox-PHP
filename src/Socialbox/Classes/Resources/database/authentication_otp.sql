create table authentication_otp
(
    peer_uuid varchar(36)                           not null comment 'The Peer UUID associated with this record'
        primary key comment 'The Peer UUID unique Index',
    secret    mediumtext                            not null comment 'The encrypted secret for the OTP',
    updated   timestamp default current_timestamp() not null comment 'The Timestamp for when the record was last updated',
    constraint authentication_otp_peer_uuid_uindex
        unique (peer_uuid) comment 'The Peer UUID unique Index',
    constraint authentication_otp_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing encrypted OTP secrets for for verification';

create index authentication_otp_updated_index
    on authentication_otp (updated)
    comment 'The index for the updated column';

