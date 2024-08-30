# SSPanel-UIM接入VouCash
SSPanel-UIM VouCash支付插件 支持USDT，关于 [VouCash](https://github.com/voucash/voucash)

### 设置

1. 下载 SDK

下载src/Services/Gateway/VouCash.php，并上传到src/Gateway/Services/目录中  
下载resources/views/tabler/gateway/voucash.tpl，并上传到resources/views/tabler/gateway/目录中  

2. 在网站根目录执行

```sh
php composer update 
```

3. 进入管理后台，管理 > 设置 > 财务，启用VouCash


### 兑现
1. 支付成功后，新的代金券将保存到`/tmp/voucher.txt`中，打开该文件
2. 复制代金券到 [VouCash提现](https://voucash.com/cn/redeem)

## 有问题和合作可以小飞机联系我们
 - telegram：@voucash
