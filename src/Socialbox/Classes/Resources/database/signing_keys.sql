create table signing_keys
(
    peer_uuid  varchar(36)                                            not null comment 'The UUID of the peer',
    uuid       varchar(36)                default uuid()              not null comment 'The UUID of the key record',
    name       varchar(64)                                            null comment 'Optional. User provided name for the key',
    public_key varchar(64)                                            not null comment 'The Public Signature Key',
    state      enum ('ACTIVE', 'EXPIRED') default 'ACTIVE'            not null comment 'The state of the public key',
    expires    timestamp                                              null comment 'The Timestamp for when this key expires, null = Never expires',
    created    timestamp                  default current_timestamp() not null comment 'The Timestamp for when the signing key record was created',
    primary key (peer_uuid, uuid) comment 'The Unique Index pair for the signing key name and the UUID of the peer',
    constraint signing_keys_peer_uuid_uuid_uindex
        unique (peer_uuid, uuid) comment 'The Unique Index pair for the signing key name and the UUID of the peer',
    constraint signing_keys_pk
        unique (peer_uuid, uuid) comment 'The Unique Index pair for the signing key name and the UUID of the peer',
    constraint signing_keys_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing public signing keys for peers on the network';

create index signing_keys_peer_uuid_index
    on signing_keys (peer_uuid)
    comment 'The primary index for the peer UUID column';

create index signing_keys_state_index
    on signing_keys (state)
    comment 'Signing key state index';

create index signing_keys_uuid_index
    on signing_keys (uuid)
    comment 'The index for the signing key namee';

