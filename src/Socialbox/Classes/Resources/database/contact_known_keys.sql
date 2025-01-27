create table contacts_known_keys
(
    contact_uuid varchar(36)                           not null comment 'The UUID of the contact in reference to',
    key_name     varchar(64)                           not null comment 'The name of the key',
    public_key   varchar(64)                           not null comment 'The public signing key',
    expires      timestamp                             not null comment 'The Timestamp for when this key expires',
    trusted_at   timestamp default current_timestamp() not null comment 'The Timestamp for when this signing key was trusted',
    primary key (contact_uuid, key_name) comment 'The unique key-name pair with the contact uuid to ensure no keys with the same names should exist',
    constraint contacts_known_keys_contact_uuid_key_name_uindex
        unique (contact_uuid, key_name) comment 'The unique key-name pair with the contact uuid to ensure no keys with the same names should exist',
    constraint contacts_known_keys_contacts_uuid_fk
        foreign key (contact_uuid) references contacts (uuid)
            on update cascade on delete cascade
)
    comment 'Table for housing known signing keys for peer contacts';

create index contacts_known_keys_key_name_index
    on contacts_known_keys (key_name)
    comment 'The index for the key name';

