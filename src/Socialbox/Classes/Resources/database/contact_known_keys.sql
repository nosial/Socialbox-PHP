create table contacts_known_keys
(
    contact_uuid   varchar(36)                           not null comment 'The Unique Universal Identifier of the personal contact that this record is associated with',
    signature_uuid varchar(36)                           not null comment 'The Unique Universal Identifier for the signature key',
    signature_name varchar(64)                           not null comment 'The name of the signing key',
    signature_key  varchar(64)                           not null comment 'The public signing key',
    expires        timestamp                             null comment 'The Timestamp for when this key expires, null means never',
    created        timestamp                             not null comment 'The Timestamp for when this key was created',
    trusted_on     timestamp default current_timestamp() not null comment 'The Timestamp for when the peer trusted this key',
    primary key (contact_uuid, signature_uuid),
    constraint contacts_known_keys_signature_uuid_contact_uuid_uindex
        unique (signature_uuid, contact_uuid) comment 'The Unique Signature Index Pair for the contact UUID and key UUID',
    constraint contacts_known_keys_contacts_uuid_fk
        foreign key (contact_uuid) references contacts (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing known keys associated with personal contact records';

create index contacts_known_keys_contact_uuid_index
    on contacts_known_keys (contact_uuid)
    comment 'The Index of the contact UUID';

