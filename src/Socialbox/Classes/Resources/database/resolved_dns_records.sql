create table resolved_dns_records
(
    domain       varchar(512)                          not null comment 'The domain name'
        primary key comment 'Unique Index for the server domain',
    rpc_endpoint text                                  not null comment 'The endpoint of the RPC server',
    public_key   text                                  not null comment 'The Public Key of the server',
    expires      bigint                                not null comment 'The Unix Timestamp for when the server''s keypair expires',
    updated      timestamp default current_timestamp() not null comment 'The Timestamp for when this record was last updated',
    constraint resolved_dns_records_domain_uindex
        unique (domain) comment 'Unique Index for the server domain',
    constraint resolved_dns_records_pk
        unique (domain) comment 'Unique Index for the server domain'
)
    comment 'A table for housing DNS resolutions';

create index resolved_dns_records_updated_index
    on resolved_dns_records (updated)
    comment 'The index for the updated column';

