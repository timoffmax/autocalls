#!/bin/sh

cid=$1;
InquiryId=$2;
dialednum=7273357870

fn=/var/spool/asterisk/outgoing/$cid.call

if [ ! -e $fn ]; then
echo "Channel: Local/$cid@autocalls
MaxRetries: 1
RetryTime: 20
WaitTime: 45
Context: from-autocalls
Extension: $dialednum
Setvar: caller=autocalls
Setvar: TIMEOUT(absolute)=900
Setvar: cid=$cid
Setvar: InquiryId=$InquiryId
Priority: 1" > $fn
fi

