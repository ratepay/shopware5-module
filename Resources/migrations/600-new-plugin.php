<?php
namespace RpayRatePay\Migrations;

use Shopware\Components\Migrations\AbstractMigration;

class Migration600 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    public function up($mode)
    {
        if(self::MODUS_UPDATE === $mode) {
            $this->addSql("
            ALTER TABLE rpay_ratepay_config
                CHANGE invoice config_invoice_id INT DEFAULT NULL,
                CHANGE debit config_debit_id INT DEFAULT NULL,
                CHANGE installment config_installment_id INT DEFAULT NULL,
                CHANGE installment0 config_installment0_id INT DEFAULT NULL,
                CHANGE installmentDebit config_installmentdebit_id INT DEFAULT NULL,
                CHANGE prepayment config_prepayment_id INT DEFAULT NULL,
                CHANGE country_code_delivery country_code_delivery VARCHAR(30) DEFAULT NULL,
                CHANGE currency currency VARCHAR(30) DEFAULT NULL
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_5365854271806BDB FOREIGN KEY (config_invoice_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_53658542F9866C30 FOREIGN KEY (config_debit_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_536585423194FB7D FOREIGN KEY (config_installment_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_536585424055D38C FOREIGN KEY (config_installment0_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_536585428A61ADCC FOREIGN KEY (config_installmentdebit_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config
                ADD CONSTRAINT FK_53658542758934E4 FOREIGN KEY (config_prepayment_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            CREATE UNIQUE INDEX UNIQ_5365854271806BDB ON rpay_ratepay_config (config_invoice_id);
            CREATE UNIQUE INDEX UNIQ_53658542F9866C30 ON rpay_ratepay_config (config_debit_id);
            CREATE UNIQUE INDEX UNIQ_536585423194FB7D ON rpay_ratepay_config (config_installment_id);
            CREATE UNIQUE INDEX UNIQ_536585424055D38C ON rpay_ratepay_config (config_installment0_id);
            CREATE UNIQUE INDEX UNIQ_536585428A61ADCC ON rpay_ratepay_config (config_installmentdebit_id);
            CREATE UNIQUE INDEX UNIQ_53658542758934E4 ON rpay_ratepay_config (config_prepayment_id);
            
            ALTER TABLE rpay_ratepay_config_installment
                ADD CONSTRAINT FK_B3353A06C6DCBE74 FOREIGN KEY (rpay_id) REFERENCES rpay_ratepay_config_payment (rpay_id)
            ;
            
            ALTER TABLE rpay_ratepay_config_payment
                CHANGE limit_max_b2b limit_max_b2b INT DEFAULT NULL,
                CHANGE address address INT DEFAULT NULL
            ;
            
            ALTER TABLE rpay_ratepay_order_discount
                DROP PRIMARY KEY
            ;
            
            ALTER TABLE rpay_ratepay_order_discount
                CHANGE s_order_detail_id s_order_details_id INT NOT NULL,
                DROP s_order_id,
                DROP tax_rate
            ;
            
            ALTER TABLE rpay_ratepay_order_discount
                ADD PRIMARY KEY (s_order_details_id)
            ;
            
            ALTER TABLE rpay_ratepay_order_history
                CHANGE event event VARCHAR(100) DEFAULT NULL,
                CHANGE articlename articlename VARCHAR(100) DEFAULT NULL,
                CHANGE articlenumber articlenumber VARCHAR(50) DEFAULT NULL,
                CHANGE quantity quantity VARCHAR(50) DEFAULT NULL
            ;
            
            ALTER TABLE rpay_ratepay_order_positions
                DROP tax_rate
            ;
            
            ALTER TABLE rpay_ratepay_order_shipping
                DROP tax_rate
            ;
            ALTER TABLE rpay_ratepay_logging
                CHANGE version version VARCHAR(10) DEFAULT 'N/A' NOT NULL,
                CHANGE operation operation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE suboperation suboperation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE transactionId transactionId VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE firstname firstname VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE lastname lastname VARCHAR(255) DEFAULT 'N/A' NOT NULL
            ;
            
            ALTER TABLE rpay_ratepay_logging
                CHANGE version version VARCHAR(10) DEFAULT 'N/A' NOT NULL,
                CHANGE operation operation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE suboperation suboperation VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE transactionId transactionId VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE firstname firstname VARCHAR(255) DEFAULT 'N/A' NOT NULL,
                CHANGE lastname lastname VARCHAR(255) DEFAULT 'N/A' NOT NULL
            ;
            ");
        }
    }
}
