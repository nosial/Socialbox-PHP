create table contacts
(
    uuid                 varchar(36)                           default uuid()              not null comment 'The contact UUID for the record'
        primary key comment 'The Primary Unique Universal Identifier for the contact record',
    peer_uuid            varchar(36)                                                       not null comment 'The Peer UUID',
    contact_peer_address varchar(256)                                                      not null comment 'The contact peer address',
    relationship         enum ('MUTUAL', 'TRUSTED', 'BLOCKED') default 'MUTUAL'            not null comment 'The relationship between the two peers, MUTUAL=The contact peer is recognized',
    created              timestamp                             default current_timestamp() not null comment 'The Timestamp for when this contact was created',
    constraint contacts_uuid_uindex
        unique (uuid) comment 'The Primary Unique Universal Identifier for the contact record',
    constraint peer_contacts_peer_uuid_contact_peer_address_uindex
        unique (peer_uuid, contact_peer_address) comment 'The Unique Peer UUID & Contact Peer Address combination pair',
    constraint peer_contacts_registered_peers_uuid_fk
        foreign key (peer_uuid) references peers (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing personal contacts for peers';

