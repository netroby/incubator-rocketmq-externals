<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace RocketMQ\Remoting\Common;

class RemotingHelper
{
    const ROCKETMQ_REMOTING = "RocketmqRemoting";
    const DEFAULT_CHARSET = "UTF-8";

    public static function exceptionSimpleDesc($e) {
        return $e->getTraceAsString();
    }

    public static function string2SocketAddress($addr) {
        $s = explode(":", $addr);
        $isa = new InetSocketAddress($s[0], (int)$s[1]);
        return $isa;
    }

    public static function invokeSync($addr, $request, $timeoutMillis) {
        $beginTime = System::currentTimeMillis();
        $socketAddress = RemotingUtil::string2SocketAddress($addr);
        $socketChannel = RemotingUtil::connect($socketAddress);
        if ($socketChannel != null) {
            $sendRequestOK = false;


                $socketChannel->configureBlocking(true);

                //bugfix  http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=4614802
                $socketChannel->socket()->setSoTimeout((int)$timeoutMillis);

                $byteBufferRequest = request->encode();
                while ($byteBufferRequest->hasRemaining()) {
                    $length = $socketChannel.write($byteBufferRequest);
                    if ($length > 0) {
                        if ($byteBufferRequest->hasRemaining()) {
                            if ((System::currentTimeMillis() - $beginTime) > $timeoutMillis) {

                                throw new RemotingSendRequestException($addr);
                            }
                        }
                    } else {
                        throw new RemotingSendRequestException($addr);
                    }
                    sleep(1);
                }

                $sendRequestOK = true;

                $byteBufferSize = ByteBuffer->allocate(4);
                while ($byteBufferSize->hasRemaining()) {
                    $length = $socketChannel->read($byteBufferSize);
                    if ($length > 0) {
                        if ($byteBufferSize->hasRemaining()) {
                            if ((System::currentTimeMillis() - $beginTime) > $timeoutMillis) {

                                throw new RemotingTimeoutException($addr, $timeoutMillis);
                            }
                        }
                    } else {
                        throw new RemotingTimeoutException($addr, $timeoutMillis);
                    }
                    sleep(1);
                }

                $size = $byteBufferSize->getInt(0);
                $byteBufferBody = ByteBuffer->allocate($size);
                while ($byteBufferBody->hasRemaining()) {
                    $length = $socketChannel->read($byteBufferBody);
                    if ($length > 0) {
                        if ($byteBufferBody->hasRemaining()) {
                            if ((System::currentTimeMillis() - $beginTime) > $timeoutMillis) {

                                throw new RemotingTimeoutException($addr, $timeoutMillis);
                            }
                        }
                    } else {
                        throw new RemotingTimeoutException($addr, $timeoutMillis);
                    }

                    sleep(1);
                }

                $byteBufferBody->flip();
                return RemotingCommand->decode($byteBufferBody);
            } catch (\Exception $e) {
                echo $e->getTraceAsString();

                if ($sendRequestOK) {
                    throw new RemotingTimeoutException($addr, $timeoutMillis);
                } else {
                    throw new RemotingSendRequestException($addr);
                }
            } finally {
                    $socketChannel->close();
            }
        } else {
            throw new RemotingConnectException($addr);
        }
    }
    public static function parseChannelRemoteAddr($channel) {
        if (null === $channel) {
            return "";
        }
        $remote = $channel.remoteAddress();
        $addr = $remote !== null ? $remote.toString() : "";
        if (strlen($addr) > 0) {
            $index = strpos("/", $addr, -1);
            if ($index > -1) {
                return substr($addr, $index+1);
            }
            return $addr;
        }
        return "";
    }
    public static function parseChannelRemoteName($channel) {
        if (null === $channel) {
            return "";
        }
        $remote = $channel.remoteAddress();
        if ($remote !== null) {
            return $remote.getAddress().getHostName();
        }
        return "";
    }
    public function parseSocketAddresswAddr($socketAddress) {
        if ($socketAddress != null) {
            $addr = $socketAddress.toString();
            if (strlen($addr) > 0) {
                return substr($addr, 1);
            }
        }
        return "";
    }
    public static function parseSocketAddressName($socketAddress) {
        if ($socketAddress != null) {
            return $socketAddress.getAddress().getHostName();
        }
        return "";
    }

    
}