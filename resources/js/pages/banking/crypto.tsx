import { Head, useForm } from '@inertiajs/react';
import { Wallet, TrendingUp, ArrowUpDown, History, Send, Download, Plus, CreditCard } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

interface Wallet {
    id: number; currency: string; name: string; network: string;
    address: string; balance: number; locked_balance: number; label: string;
}

interface Rate {
    pair: string; bid: number; ask: number; mid: number; change_24h: number;
}

interface Order {
    id: number; order_number: string; side: string; base_currency: string;
    quote_currency: string; amount: number; filled_amount: number; price: number;
    fee: number; total: number; status: string; order_type: string;
    created_at: string; filled_at: string | null;
}

interface Deposit {
    id: number; reference: string; currency: string; amount: number;
    net_amount: number; status: string; tx_hash: string | null;
    confirmations: number; created_at: string;
}

interface Withdrawal {
    id: number; reference: string; currency: string; amount: number;
    net_amount: number; to_address: string; status: string; created_at: string;
}

interface Currency {
    id: number; code: string; name: string; network: string;
    minimum_withdrawal: number; withdrawal_fee: number;
    minimum_deposit: number; deposit_fee: number;
}

interface BankAccount {
    id: number; label: string; number: string; balance: number; currency: string;
}

export default function Crypto({ wallets, rates, orders, deposits, withdrawals, currencies, accounts }: {
    wallets: Wallet[]; rates: Rate[]; orders: Order[];
    deposits: Deposit[]; withdrawals: Withdrawal[]; currencies: Currency[];
    accounts: BankAccount[];
}) {
    const orderForm = useForm({ base_currency: 'BTC', quote_currency: 'EUR', side: 'buy', amount: '', price: '', order_type: 'market', action: 'place_order' });
    const walletForm = useForm({ currency_code: '', label: '', action: 'create_wallet' });
    const withdrawForm = useForm({ wallet_id: '', amount: '', to_address: '', action: 'request_withdrawal' });
    const depositForm = useForm({ crypto_currency_id: '', crypto_wallet_id: '', amount: '', tx_hash: '', from_address: '', action: 'record_deposit' });
    const buyFiatForm = useForm({ account_id: '', crypto_code: 'BTC', amount: '', external_address: '', action: 'buy_with_fiat' });
    const sellFiatForm = useForm({ account_id: '', crypto_code: 'BTC', amount: '', action: 'sell_to_fiat' });

    const submitOrder = (e: React.FormEvent) => { e.preventDefault(); orderForm.post(route('banking.crypto')); };
    const submitWallet = (e: React.FormEvent) => { e.preventDefault(); walletForm.post(route('banking.crypto')); };
    const submitWithdraw = (e: React.FormEvent) => { e.preventDefault(); withdrawForm.post(route('banking.crypto')); };
    const submitDeposit = (e: React.FormEvent) => { e.preventDefault(); depositForm.post(route('banking.crypto')); };
    const submitBuyFiat = (e: React.FormEvent) => { e.preventDefault(); buyFiatForm.post(route('banking.crypto')); };
    const submitSellFiat = (e: React.FormEvent) => { e.preventDefault(); sellFiatForm.post(route('banking.crypto')); };

    const quote = rates[0];
    const selectedRate = rates.find(r => r.pair.startsWith(orderForm.data.base_currency));
    const buyRate = rates.find(r => r.pair.startsWith(buyFiatForm.data.crypto_code));
    const selectedAccount = accounts.find(a => String(a.id) === buyFiatForm.data.account_id);
    const sellRate = rates.find(r => r.pair.startsWith(sellFiatForm.data.crypto_code));
    const selectedWallet = wallets.find(w => w.currency === sellFiatForm.data.crypto_code);

    return (
        <>
            <Head title="Crypto Exchange" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Crypto Exchange</h1>
                    <p className="text-muted-foreground">Trade, send, and receive cryptocurrencies</p>
                </div>

                <Tabs defaultValue="exchange">
                    <TabsList>
                        <TabsTrigger value="exchange"><TrendingUp className="mr-2 h-4 w-4" />Exchange</TabsTrigger>
                        <TabsTrigger value="wallets"><Wallet className="mr-2 h-4 w-4" />Wallets</TabsTrigger>
                        <TabsTrigger value="orders"><History className="mr-2 h-4 w-4" />Orders</TabsTrigger>
                        <TabsTrigger value="deposits"><Download className="mr-2 h-4 w-4" />Deposits</TabsTrigger>
                        <TabsTrigger value="withdrawals"><Send className="mr-2 h-4 w-4" />Withdrawals</TabsTrigger>
                        <TabsTrigger value="buy"><CreditCard className="mr-2 h-4 w-4" />Buy Crypto</TabsTrigger>
                        <TabsTrigger value="sell"><ArrowUpDown className="mr-2 h-4 w-4" />Sell Crypto</TabsTrigger>
                    </TabsList>

                    <TabsContent value="exchange" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle>Place Order</CardTitle></CardHeader>
                                <CardContent>
                                    <form onSubmit={submitOrder} className="space-y-4">
                                        <div className="grid grid-cols-2 gap-2">
                                            <Button type="button" variant={orderForm.data.side === 'buy' ? 'default' : 'outline'} onClick={() => orderForm.setData('side', 'buy')} className={orderForm.data.side === 'buy' ? 'bg-green-600 hover:bg-green-700' : ''}>Buy</Button>
                                            <Button type="button" variant={orderForm.data.side === 'sell' ? 'default' : 'outline'} onClick={() => orderForm.setData('side', 'sell')} className={orderForm.data.side === 'sell' ? 'bg-red-600 hover:bg-red-700' : ''}>Sell</Button>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>Currency</Label>
                                                <Select value={orderForm.data.base_currency} onValueChange={v => orderForm.setData('base_currency', v)}>
                                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                                    <SelectContent>{currencies.filter(c => c.code !== 'USDT').map(c => (<SelectItem key={c.code} value={c.code}>{c.code}</SelectItem>))}</SelectContent>
                                                </Select>
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Order Type</Label>
                                                <Select value={orderForm.data.order_type} onValueChange={v => orderForm.setData('order_type', v)}>
                                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="market">Market</SelectItem>
                                                        <SelectItem value="limit">Limit</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Amount ({orderForm.data.base_currency})</Label>
                                            <Input type="number" step="any" min="0.00000001" value={orderForm.data.amount} onChange={e => orderForm.setData('amount', e.target.value)} required />
                                            <InputError message={orderForm.errors.amount} />
                                        </div>
                                        {orderForm.data.order_type === 'limit' && (
                                            <div className="space-y-2">
                                                <Label>Price (EUR)</Label>
                                                <Input type="number" step="0.01" value={orderForm.data.price} onChange={e => orderForm.setData('price', e.target.value)} required />
                                            </div>
                                        )}
                                        {selectedRate && (
                                            <div className="rounded-lg bg-muted p-3 text-sm">
                                                <div className="flex justify-between"><span>Market price</span><span>€{orderForm.data.side === 'buy' ? Number(selectedRate.ask).toFixed(2) : Number(selectedRate.bid).toFixed(2)}</span></div>
                                                <div className="flex justify-between mt-1"><span>Estimated total</span><span>€{(parseFloat(orderForm.data.amount || '0') * (orderForm.data.side === 'buy' ? selectedRate.ask : selectedRate.bid)).toFixed(2)}</span></div>
                                            </div>
                                        )}
                                        <Button type="submit" disabled={orderForm.processing} className={orderForm.data.side === 'buy' ? 'bg-green-600 hover:bg-green-700 w-full' : 'bg-red-600 hover:bg-red-700 w-full'}>
                                            {orderForm.data.side === 'buy' ? 'Buy' : 'Sell'} {orderForm.data.base_currency}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Market Rates</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    {rates.map(r => (
                                        <div key={r.pair} className="flex items-center justify-between rounded-lg border p-3">
                                            <div className="flex items-center gap-2">
                                                <TrendingUp className="size-4 text-muted-foreground" />
                                                <span className="font-medium">{r.pair}</span>
                                            </div>
                                            <div className="flex items-center gap-3 text-sm">
                                                <span className="text-muted-foreground">B: €{Number(r.bid).toFixed(2)}</span>
                                                <span className="text-muted-foreground">A: €{Number(r.ask).toFixed(2)}</span>
                                                <Badge variant={r.change_24h >= 0 ? 'secondary' : 'destructive'}>
                                                    {r.change_24h >= 0 ? '+' : ''}{Number(r.change_24h).toFixed(2)}%
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="wallets" className="space-y-4">
                        <Card>
                            <CardHeader><CardTitle>Your Wallets</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                {wallets.map(w => (
                                    <div key={w.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20">
                                                <Wallet className="size-5 text-amber-600" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{w.currency} — {w.name}</p>
                                                <p className="font-mono text-xs text-muted-foreground">{w.network}</p>
                                                <p className="font-mono text-xs text-muted-foreground">{w.address.slice(0, 10)}...{w.address.slice(-6)}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-bold">{w.balance.toLocaleString(undefined, { maximumFractionDigits: 8 })} {w.currency}</p>
                                            {w.locked_balance > 0 && <p className="text-xs text-muted-foreground">{w.locked_balance} locked</p>}
                                            <p className="text-xs text-muted-foreground">{w.label}</p>
                                        </div>
                                    </div>
                                ))}
                                {wallets.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">No wallets yet.</p>}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle><Plus className="mr-2 inline h-4 w-4" />Create Wallet</CardTitle></CardHeader>
                            <CardContent>
                                <form onSubmit={submitWallet} className="flex gap-4 items-end">
                                    <div className="flex-1 space-y-2">
                                        <Label>Currency</Label>
                                        <Select value={walletForm.data.currency_code} onValueChange={v => walletForm.setData('currency_code', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select currency" /></SelectTrigger>
                                            <SelectContent>{currencies.map(c => (<SelectItem key={c.code} value={c.code}>{c.code} — {c.name} ({c.network})</SelectItem>))}</SelectContent>
                                        </Select>
                                        <InputError message={walletForm.errors.currency_code} />
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <Label>Label (optional)</Label>
                                        <Input value={walletForm.data.label} onChange={e => walletForm.setData('label', e.target.value)} placeholder="My Wallet" />
                                    </div>
                                    <Button type="submit" disabled={walletForm.processing}><Plus className="mr-1 h-4 w-4" />Create</Button>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="orders" className="space-y-4">
                        <Card>
                            <CardHeader><CardTitle>Order History</CardTitle></CardHeader>
                            <CardContent>
                                {orders.length === 0 ? <p className="text-sm text-muted-foreground">No orders yet.</p> : (
                                    <div className="space-y-3">
                                        {orders.map(o => (
                                            <div key={o.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <p className="font-medium">{o.order_number}</p>
                                                    <p className="text-xs text-muted-foreground">{o.side.toUpperCase()} {o.base_currency}/{o.quote_currency} · {o.order_type}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium">{o.amount} {o.base_currency} @ €{Number(o.price).toFixed(2)}</p>
                                                    <div className="flex items-center gap-2 justify-end">
                                                        <span className="text-xs text-muted-foreground">Filled: {o.filled_amount}</span>
                                                        <Badge variant={o.status === 'filled' ? 'default' : o.status === 'open' ? 'secondary' : 'destructive'}>{o.status}</Badge>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="deposits" className="space-y-4">
                        <Card>
                            <CardHeader><CardTitle>Record Deposit</CardTitle></CardHeader>
                            <CardContent>
                                <form onSubmit={submitDeposit} className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Currency</Label>
                                            <Select value={String(depositForm.data.crypto_currency_id)} onValueChange={v => depositForm.setData('crypto_currency_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select currency" /></SelectTrigger>
                                                <SelectContent>{currencies.map(c => (<SelectItem key={c.code} value={String(c.id)}>{c.code}</SelectItem>))}</SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Wallet</Label>
                                            <Select value={String(depositForm.data.crypto_wallet_id)} onValueChange={v => depositForm.setData('crypto_wallet_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select wallet" /></SelectTrigger>
                                                <SelectContent>{wallets.map(w => (<SelectItem key={w.id} value={String(w.id)}>{w.currency} — {w.label}</SelectItem>))}</SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Amount</Label>
                                            <Input type="number" step="any" min="0" value={depositForm.data.amount} onChange={e => depositForm.setData('amount', e.target.value)} required />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>TX Hash (optional)</Label>
                                            <Input value={depositForm.data.tx_hash} onChange={e => depositForm.setData('tx_hash', e.target.value)} />
                                        </div>
                                    </div>
                                    <Button type="submit" disabled={depositForm.processing}>Record Deposit</Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Deposit History</CardTitle></CardHeader>
                            <CardContent>
                                {deposits.length === 0 ? <p className="text-sm text-muted-foreground">No deposits yet.</p> : (
                                    <div className="space-y-3">
                                        {deposits.map(d => (
                                            <div key={d.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <p className="font-medium">{d.reference}</p>
                                                    <p className="text-xs text-muted-foreground">{d.tx_hash ? d.tx_hash.slice(0, 10) + '...' : 'Pending'}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium">{d.net_amount} {d.currency}</p>
                                                    <Badge variant={d.status === 'confirmed' ? 'default' : 'secondary'}>{d.status}</Badge>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="withdrawals" className="space-y-4">
                        <Card>
                            <CardHeader><CardTitle>Request Withdrawal</CardTitle></CardHeader>
                            <CardContent>
                                <form onSubmit={submitWithdraw} className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>From Wallet</Label>
                                            <Select value={String(withdrawForm.data.wallet_id)} onValueChange={v => withdrawForm.setData('wallet_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select wallet" /></SelectTrigger>
                                                <SelectContent>{wallets.map(w => (<SelectItem key={w.id} value={String(w.id)}>{w.currency} — €{Number(w.balance).toFixed(2)}</SelectItem>))}</SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Amount</Label>
                                            <Input type="number" step="any" min="0.00000001" value={withdrawForm.data.amount} onChange={e => withdrawForm.setData('amount', e.target.value)} required />
                                            <InputError message={withdrawForm.errors.amount} />
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Destination Address</Label>
                                        <Input value={withdrawForm.data.to_address} onChange={e => withdrawForm.setData('to_address', e.target.value)} placeholder="0x... or address" required />
                                    </div>
                                    <Button type="submit" disabled={withdrawForm.processing}><Send className="mr-2 h-4 w-4" />Request Withdrawal</Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Withdrawal History</CardTitle></CardHeader>
                            <CardContent>
                                {withdrawals.length === 0 ? <p className="text-sm text-muted-foreground">No withdrawals yet.</p> : (
                                    <div className="space-y-3">
                                        {withdrawals.map(w => (
                                            <div key={w.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <p className="font-medium">{w.reference}</p>
                                                    <p className="text-xs text-muted-foreground font-mono">{w.to_address.slice(0, 10)}...{w.to_address.slice(-6)}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium">-{w.net_amount} {w.currency}</p>
                                                    <Badge variant={w.status === 'approved' ? 'default' : w.status === 'pending' ? 'secondary' : 'destructive'}>{w.status}</Badge>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                    <TabsContent value="buy" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle><CreditCard className="mr-2 inline h-4 w-4" />Buy with Fiat</CardTitle></CardHeader>
                                <CardContent>
                                    <form onSubmit={submitBuyFiat} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>From Account *</Label>
                                            <Select value={buyFiatForm.data.account_id} onValueChange={v => buyFiatForm.setData('account_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select EUR account" /></SelectTrigger>
                                                <SelectContent>{accounts.filter(a => a.currency === 'EUR').map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toLocaleString()})</SelectItem>))}</SelectContent>
                                            </Select>
                                            <InputError message={buyFiatForm.errors.account_id} />
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>Buy *</Label>
                                                <Select value={buyFiatForm.data.crypto_code} onValueChange={v => buyFiatForm.setData('crypto_code', v)}>
                                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                                    <SelectContent>{currencies.map(c => (<SelectItem key={c.code} value={c.code}>{c.code} — {c.name}</SelectItem>))}</SelectContent>
                                                </Select>
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Amount (EUR) *</Label>
                                                <Input type="number" step="0.01" min="1" value={buyFiatForm.data.amount} onChange={e => buyFiatForm.setData('amount', e.target.value)} required />
                                                <InputError message={buyFiatForm.errors.amount} />
                                            </div>
                                        </div>
                                        {buyRate && parseFloat(buyFiatForm.data.amount || '0') > 0 && (
                                            <div className="rounded-lg bg-muted p-3 text-sm space-y-1">
                                                <div className="flex justify-between">
                                                    <span>Rate</span>
                                                    <span>1 {buyFiatForm.data.crypto_code} = €{Number(buyRate.mid).toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between font-medium">
                                                    <span>You receive</span>
                                                    <span>~{(parseFloat(buyFiatForm.data.amount || '0') / buyRate.mid).toFixed(8)} {buyFiatForm.data.crypto_code}</span>
                                                </div>
                                                <div className="flex justify-between text-xs text-muted-foreground">
                                                    <span>Fee (0.2%)</span>
                                                    <span>€{(parseFloat(buyFiatForm.data.amount || '0') * 0.002).toFixed(2)}</span>
                                                </div>
                                            </div>
                                        )}
                                        <div className="space-y-2">
                                            <Label>Send to External Wallet (optional)</Label>
                                            <Input value={buyFiatForm.data.external_address} onChange={e => buyFiatForm.setData('external_address', e.target.value)} placeholder="Leave empty for virtual wallet" />
                                            <p className="text-xs text-muted-foreground">Enter a blockchain address to send directly to your physical wallet, or leave blank to keep in your virtual wallet.</p>
                                        </div>
                                        <Button type="submit" disabled={buyFiatForm.processing} className="w-full bg-green-600 hover:bg-green-700">
                                            <CreditCard className="mr-2 h-4 w-4" />Buy {buyFiatForm.data.crypto_code}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Your Virtual Wallets</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    {wallets.length === 0 && <p className="text-sm text-muted-foreground">No wallets yet. Create one or buy crypto to get started.</p>}
                                    {wallets.map(w => (
                                        <div key={w.id} className="flex items-center justify-between rounded-lg border p-3">
                                            <div className="flex items-center gap-2">
                                                <Wallet className="size-4 text-muted-foreground" />
                                                <span className="font-medium">{w.currency}</span>
                                            </div>
                                            <div className="text-right">
                                                <p className="font-medium">{Number(w.balance).toFixed(8)}</p>
                                                <p className="text-xs text-muted-foreground font-mono">{w.address.slice(0, 8)}...{w.address.slice(-4)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                    <TabsContent value="sell" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle><ArrowUpDown className="mr-2 inline h-4 w-4" />Sell to Fiat</CardTitle></CardHeader>
                                <CardContent>
                                    <form onSubmit={submitSellFiat} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label>To Account *</Label>
                                            <Select value={sellFiatForm.data.account_id} onValueChange={v => sellFiatForm.setData('account_id', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select EUR account" /></SelectTrigger>
                                                <SelectContent>{accounts.filter(a => a.currency === 'EUR').map(a => (<SelectItem key={a.id} value={String(a.id)}>{a.label} (€{Number(a.balance).toLocaleString()})</SelectItem>))}</SelectContent>
                                            </Select>
                                            <InputError message={sellFiatForm.errors.account_id} />
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label>Sell *</Label>
                                                <Select value={sellFiatForm.data.crypto_code} onValueChange={v => sellFiatForm.setData('crypto_code', v)}>
                                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                                    <SelectContent>{currencies.map(c => (<SelectItem key={c.code} value={c.code}>{c.code} — {c.name}</SelectItem>))}</SelectContent>
                                                </Select>
                                            </div>
                                            <div className="space-y-2">
                                                <Label>Amount ({sellFiatForm.data.crypto_code}) *</Label>
                                                <Input type="number" step="any" min="0.00000001" value={sellFiatForm.data.amount} onChange={e => sellFiatForm.setData('amount', e.target.value)} required />
                                                <InputError message={sellFiatForm.errors.amount} />
                                            </div>
                                        </div>
                                        {selectedWallet && (
                                            <div className="text-xs text-muted-foreground">
                                                Available: {Number(selectedWallet.balance).toFixed(8)} {selectedWallet.currency}
                                            </div>
                                        )}
                                        {sellRate && parseFloat(sellFiatForm.data.amount || '0') > 0 && (
                                            <div className="rounded-lg bg-muted p-3 text-sm space-y-1">
                                                <div className="flex justify-between">
                                                    <span>Rate</span>
                                                    <span>1 {sellFiatForm.data.crypto_code} = €{Number(sellRate.mid).toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between font-medium">
                                                    <span>You receive</span>
                                                    <span>~€{(parseFloat(sellFiatForm.data.amount || '0') * sellRate.mid * 0.998).toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between text-xs text-muted-foreground">
                                                    <span>Fee (0.2%)</span>
                                                    <span>€{(parseFloat(sellFiatForm.data.amount || '0') * sellRate.mid * 0.002).toFixed(2)}</span>
                                                </div>
                                            </div>
                                        )}
                                        <Button type="submit" disabled={sellFiatForm.processing} className="w-full bg-red-600 hover:bg-red-700">
                                            <ArrowUpDown className="mr-2 h-4 w-4" />Sell {sellFiatForm.data.crypto_code}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader><CardTitle>Your Virtual Wallets</CardTitle></CardHeader>
                                <CardContent className="space-y-3">
                                    {wallets.length === 0 && <p className="text-sm text-muted-foreground">No wallets yet.</p>}
                                    {wallets.map(w => (
                                        <div key={w.id} className="flex items-center justify-between rounded-lg border p-3">
                                            <div className="flex items-center gap-2">
                                                <Wallet className="size-4 text-muted-foreground" />
                                                <span className="font-medium">{w.currency}</span>
                                            </div>
                                            <div className="text-right">
                                                <p className="font-medium">{Number(w.balance).toFixed(8)}</p>
                                                <p className="text-xs text-muted-foreground font-mono">{w.address.slice(0, 8)}...{w.address.slice(-4)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
