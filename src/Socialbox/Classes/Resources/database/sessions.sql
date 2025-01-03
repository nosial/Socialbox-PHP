create table sessions
(
    uuid                            varchar(36)                                          default uuid()              not null comment 'The Unique Primary index for the session UUID'
        primary key,
    peer_uuid                       varchar(36)                                                                      not null comment 'The peer the session is identified as, null if the session isn''t identified as a peer',
    client_name                     varchar(256)                                                                     not null comment 'The name of the client that is using this session',
    client_version                  varchar(16)                                                                      not null comment 'The version of the client',
    authenticated                   tinyint(1)                                           default 0                   not null comment 'Indicates if the session is currently authenticated as the peer',
    client_public_signing_key       varchar(64)                                                                      not null comment 'The client''s public signing key used for signing decrypted messages',
    client_public_encryption_key    varchar(64)                                                                      not null comment 'The Public Key of the client''s encryption key',
    server_public_encryption_key    varchar(64)                                                                      not null comment 'The server''s public encryption key for this session',
    server_private_encryption_key   varchar(64)                                                                      not null comment 'The server''s private encryption key for this session',
    private_shared_secret           varchar(64)                                                                      null comment 'The shared secret encryption key between the Client & Server',
    client_transport_encryption_key varchar(64)                                                                      null comment 'The encryption key for sending messages to the client',
    server_transport_encryption_key varchar(64)                                                                      null comment 'The encryption key for sending messages to the server',
    state                           enum ('AWAITING_DHE', 'ACTIVE', 'CLOSED', 'EXPIRED') default 'AWAITING_DHE'      not null comment 'The status of the session',
    flags                           text                                                                             null comment 'The current flags that is set to the session',
    created                         timestamp                                            default current_timestamp() not null comment 'The Timestamp for when the session was last created',
    last_request                    timestamp                                                                        null comment 'The Timestamp for when the last request was made using this session',
    constraint sessions_uuid_uindex
        unique (uuid) comment 'The Unique Primary index for the session UUID',
    constraint sessions_registered_peers_uuid_fk
        foreign key (peer_uuid) references registered_peers (uuid)
            on update cascade on delete cascade
);

create index sessions_authenticated_peer_index
    on sessions (peer_uuid)
    comment 'The Index for the authenticated peer column';

create index sessions_created_index
    on sessions (created)
    comment 'The Index for the created column of the session';

