create table external_sessions
(
    domain                          varchar(256)                                                            not null comment 'The unique domain name that this session belongs to'
        primary key comment 'The Unique Primary index for the external session',
    rpc_endpoint                    text                                                                    not null comment 'The RPC endpoint of the external connection',
    session_uuid                    varchar(36)                                                             not null comment 'The UUID of the session to the external server',
    transport_encryption_algorithm  enum ('xchacha20', 'chacha20', 'aes256gcm') default 'xchacha20'         not null comment 'The transport encryption algorithm used',
    server_keypair_expires          int                                                                     not null comment 'The Timestamp for when the server keypair expires',
    server_public_signing_key       varchar(64)                                                             not null comment 'The public signing key of the server resolved from DNS records',
    server_public_encryption_key    varchar(64)                                                             not null comment 'The public encryption key of the server for this session',
    host_public_encryption_key      varchar(64)                                                             not null comment 'The public encryption key for the host',
    host_private_encryption_key     varchar(64)                                                             not null comment 'The private encryption key for host',
    private_shared_secret           varchar(64)                                                             not null comment 'The private shared secret obtained from the DHE procedure',
    host_transport_encryption_key   varchar(64)                                                             not null comment 'The transport encryption key for the host',
    server_transport_encryption_key varchar(64)                                                             not null comment 'The transport encryption key for the server',
    last_accessed                   timestamp                                   default current_timestamp() not null comment 'The Timestamp for when the record was last accessed',
    created                         timestamp                                   default current_timestamp() not null comment 'The Timestamp for when this record was created',
    constraint external_sessions_domain_uindex
        unique (domain) comment 'The Unique Primary index for the external session'
)
    comment 'Table for housing external sessions to external servers';

