create table sessions
(
    uuid                    varchar(36)                          default uuid()              not null comment 'The Unique Primary index for the session UUID'
        primary key,
    authenticated_peer_uuid varchar(36)                                                      null comment 'The peer the session is authenticated as, null if the session isn''t authenticated',
    public_key              blob                                                             not null comment 'The client''s public key provided when creating the session',
    state                   enum ('ACTIVE', 'EXPIRED', 'CLOSED') default 'ACTIVE'            not null comment 'The status of the session',
    created                 timestamp                            default current_timestamp() not null comment 'The Timestamp for when the session was last created',
    last_request            timestamp                                                        null comment 'The Timestamp for when the last request was made using this session',
    constraint sessions_uuid_uindex
        unique (uuid) comment 'The Unique Primary index for the session UUID',
    constraint sessions_registered_peers_uuid_fk
        foreign key (authenticated_peer_uuid) references registered_peers (uuid)
            on update cascade on delete cascade
);

create index sessions_authenticated_peer_index
    on sessions (authenticated_peer_uuid)
    comment 'The Index for the authenticated peer column';

create index sessions_created_index
    on sessions (created)
    comment 'The Index for the created column of the session';

