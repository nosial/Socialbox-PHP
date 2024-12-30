create table external_sessions
(
    uuid         varchar(36) default uuid()              not null comment 'The UUID of the session for the external connection'
        primary key comment 'The Unique Primary Index for the session UUID',
    peer_uuid    varchar(36)                             not null comment 'The peer UUID that opened the connection',
    session_uuid varchar(36)                             null comment 'The UUID of the parent session responsible for this external session',
    server       varchar(255)                            null comment 'The domain of the remote server that ths external session is authorized for',
    created      timestamp   default current_timestamp() not null comment 'The Timestamp for when this record was created',
    last_used    timestamp   default current_timestamp() not null comment 'The Timestamp for when this session was last used',
    constraint external_sessions_uuid_uindex
        unique (uuid) comment 'The Unique Primary Index for the session UUID',
    constraint external_sessions_registered_peers_uuid_fk
        foreign key (peer_uuid) references registered_peers (uuid)
            on update cascade on delete cascade,
    constraint external_sessions_sessions_uuid_fk
        foreign key (session_uuid) references sessions (uuid)
)
    comment 'Table for housing external sessions from local to remote servers';

create index external_sessions_created_index
    on external_sessions (created)
    comment 'The Index for the created column';

create index external_sessions_last_used_index
    on external_sessions (last_used)
    comment 'The inex for the last used column';

create index external_sessions_peer_uuid_index
    on external_sessions (peer_uuid)
    comment 'The Index for the peer UUID';

create index external_sessions_session_uuid_index
    on external_sessions (session_uuid)
    comment 'The index for the original session uuid';

