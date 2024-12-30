create table resolved_servers
(
    domain     varchar(512)                          not null comment 'The domain name'
        primary key comment 'Unique Index for the server domain',
    endpoint   text                                  not null comment 'The endpoint of the RPC server',
    public_key text                                  not null comment 'The Public Key of the server',
    updated    timestamp default current_timestamp() not null comment 'The TImestamp for when this record was last updated',
    constraint resolved_servers_domain_uindex
        unique (domain) comment 'Unique Index for the server domain'
)
    comment 'A table for housing DNS resolutions';

