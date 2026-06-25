import { Head, useForm, router } from '@inertiajs/react';
import { Tablet, CreditCard, Play, Pause, Trash2, RotateCcw, ShieldCheck, Settings, QrCode, Wifi, Coins, Globe, Link } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useState } from 'react';

interface Account {
    id: number;
    number: string;
    label: string;
    balance: number;
    currency: string;
}

interface PosTerminal {
    id: number;
    serial_number: string;
    oracle_terminal_id: string | null;
    label: string;
    model: string;
    crypto_processor_enabled: boolean;
    default_crypto_currency: string;
    settlement_mode: string;
    oracle_api_url: string | null;
    oracle_api_key: string | null;
    status: string;
    account_number: string;
    account_label: string;
    paired_at: string;
    last_active_at: string | null;
}

interface PosTransaction {
    id: number;
    terminal_reference: string;
    terminal_serial: string;
    amount: number;
    currency: string;
    card_brand: string;
    card_last4: string;
    payment_method: string;
    payment_type: string;
    crypto_currency: string | null;
    crypto_amount: number | null;
    crypto_address: string | null;
    tx_hash: string | null;
    status: string;
    created_at: string;
}

export default function PosDashboard({
    terminals,
    pos_transactions,
    accounts,
}: {
    terminals: PosTerminal[];
    pos_transactions: PosTransaction[];
    accounts: Account[];
}) {
    const pairForm = useForm({
        account_id: '',
        serial_number: '',
        label: '',
        model: 'PAX A77',
        pairing_code: '',
    });

    const toggleForm = useForm({});
    const deleteForm = useForm({});
    const refundForm = useForm({});

    const [editingTerminalId, setEditingTerminalId] = useState<number | null>(null);
    const [simulatingTerminalId, setSimulatingTerminalId] = useState<number | null>(null);

    // Config form for Oracle and Crypto SmartPOS
    const configForm = useForm({
        oracle_terminal_id: '',
        crypto_processor_enabled: false,
        default_crypto_currency: 'USDC',
        settlement_mode: 'fiat',
        oracle_api_url: '',
        oracle_api_key: '',
    });

    // Simulation states
    const [simAmount, setSimAmount] = useState('50.00');
    const [pendingInvoice, setPendingInvoice] = useState<any>(null);
    const [simulatingPayment, setSimulatingPayment] = useState(false);

    const startEditing = (t: PosTerminal) => {
        setEditingTerminalId(editingTerminalId === t.id ? null : t.id);
        configForm.setData({
            oracle_terminal_id: t.oracle_terminal_id || '',
            crypto_processor_enabled: t.crypto_processor_enabled || false,
            default_crypto_currency: t.default_crypto_currency || 'USDC',
            settlement_mode: t.settlement_mode || 'fiat',
            oracle_api_url: t.oracle_api_url || '',
            oracle_api_key: t.oracle_api_key || '',
        });
    };

    const handleSaveConfig = (e: React.FormEvent, terminalId: number) => {
        e.preventDefault();
        configForm.post(route('banking.pos.terminals.configure', terminalId), {
            onSuccess: () => {
                setEditingTerminalId(null);
            }
        });
    };

    const startSimulation = (t: PosTerminal) => {
        setSimulatingTerminalId(t.id);
        setPendingInvoice(null);
    };

    const handleCreateSimulationCharge = async () => {
        const terminal = terminals.find(t => t.id === simulatingTerminalId);
        if (!terminal) return;

        try {
            const response = await fetch('/api/pos/oracle/charge', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    oracle_terminal_id: terminal.oracle_terminal_id || `MOCK-${terminal.serial_number}`,
                    amount: parseFloat(simAmount),
                    currency: 'EUR',
                })
            });

            const data = await response.json();
            if (response.ok) {
                setPendingInvoice(data);
            } else {
                alert(data.message || 'Failed to create simulated charge.');
            }
        } catch (err: any) {
            alert(err.message || 'Error occurred during charge generation.');
        }
    };

    const handlePaySimulationCharge = async () => {
        if (!pendingInvoice) return;
        setSimulatingPayment(true);

        try {
            const response = await fetch('/api/pos/oracle/simulate-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    reference: pendingInvoice.reference,
                    tx_hash: '0x' + Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10) + Math.random().toString(16).substring(2, 10),
                })
            });

            const data = await response.json();
            if (response.ok) {
                alert('Blockchain transfer confirmed! Payout settled successfully.');
                setPendingInvoice(null);
                setSimulatingTerminalId(null);
                router.reload();
            } else {
                alert(data.message || 'Simulation payment failed.');
            }
        } catch (err: any) {
            alert(err.message || 'Error during payment simulation.');
        } finally {
            setSimulatingPayment(false);
        }
    };

    const handlePair = (e: React.FormEvent) => {
        e.preventDefault();
        pairForm.post(route('banking.pos.terminals.store'), {
            onSuccess: () => {
                pairForm.reset('serial_number', 'label', 'pairing_code');
            },
        });
    };

    const handleToggle = (terminalId: number) => {
        toggleForm.post(route('banking.pos.terminals.toggle', terminalId));
    };

    const handleDelete = (terminalId: number) => {
        if (confirm('Are you sure you want to de-register this terminal?')) {
            deleteForm.delete(route('banking.pos.terminals.destroy', terminalId));
        }
    };

    const handleRefund = (transactionId: number) => {
        if (confirm('Are you sure you want to refund this card-present payment? This will deduct the amount from your linked account.')) {
            refundForm.post(route('banking.pos.transactions.refund', transactionId));
        }
    };

    // Calculate metrics
    const activeTerminalsCount = terminals.filter(t => t.status === 'active').length;
    const totalSalesToday = pos_transactions
        .filter(t => t.status === 'completed' && new Date(t.created_at).toDateString() === new Date().toDateString())
        .reduce((sum, t) => sum + Number(t.amount), 0);

    return (
        <>
            <Head title="POS Terminals & Gateway" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-2">
                    <h1 className="text-2xl font-bold">POS Terminal Gateway</h1>
                    <p className="text-muted-foreground text-sm">
                        Pair card readers, activate merchant hardware terminals, and issue immediate customer payment refunds.
                    </p>
                </div>

                {/* Dashboard Metrics */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Paired Readers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{terminals.length} Terminals</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {activeTerminalsCount} active, {terminals.length - activeTerminalsCount} inactive
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Today's Sales Volume</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">€{totalSalesToday.toFixed(2)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Real-time card reader sales</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Gateway Shield Status</CardTitle>
                        </CardHeader>
                        <CardContent className="flex items-center gap-2">
                            <ShieldCheck className="h-6 w-6 text-emerald-500" />
                            <div>
                                <div className="font-semibold text-sm">Compliance Active</div>
                                <p className="text-xs text-muted-foreground">Automatic AML screening</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Pairing Form */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Tablet className="h-5 w-5 text-primary" />
                                Pair New Reader
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handlePair} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="account_id">Payout Credit Account *</Label>
                                    <Select
                                        value={pairForm.data.account_id}
                                        onValueChange={v => pairForm.setData('account_id', v)}
                                    >
                                        <SelectTrigger id="account_id">
                                            <SelectValue placeholder="Select account for payouts" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accounts.map(a => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.label} ({a.currency} - {Number(a.balance).toFixed(2)})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={pairForm.errors.account_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="serial_number">Terminal Serial Number *</Label>
                                    <Input
                                        id="serial_number"
                                        placeholder="e.g. ST-WISEPOS-12345"
                                        value={pairForm.data.serial_number}
                                        onChange={e => pairForm.setData('serial_number', e.target.value)}
                                        required
                                    />
                                    <InputError message={pairForm.errors.serial_number} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="label">Terminal Label / Name</Label>
                                    <Input
                                        id="label"
                                        placeholder="e.g. Register 1 Front Desk"
                                        value={pairForm.data.label}
                                        onChange={e => pairForm.setData('label', e.target.value)}
                                    />
                                    <InputError message={pairForm.errors.label} />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="model">Device Model</Label>
                                        <Input
                                            id="model"
                                            value={pairForm.data.model}
                                            onChange={e => pairForm.setData('model', e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="pairing_code">Pairing Code</Label>
                                        <Input
                                            id="pairing_code"
                                            placeholder="e.g. 123-456"
                                            value={pairForm.data.pairing_code}
                                            onChange={e => pairForm.setData('pairing_code', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <Button type="submit" disabled={pairForm.processing} className="w-full">
                                    Pair Terminal
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Terminals & Transactions List */}
                    <div className="lg:col-span-2 flex flex-col gap-6">
                        {/* Paired Readers */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Paired Terminal Devices</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {terminals.length === 0 && (
                                    <p className="text-sm text-muted-foreground py-4 text-center">
                                        No terminals paired yet. Use the pairing form to connect a device.
                                    </p>
                                )}
                                {terminals.map(t => (
                                    <div key={t.id} className="rounded-lg border p-4 gap-4 flex flex-col">
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div className="flex items-start gap-3">
                                                <Tablet className="size-8 text-primary mt-0.5" />
                                                <div>
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-semibold text-sm">{t.label || 'Unnamed Terminal'}</span>
                                                        <Badge variant={t.status === 'active' ? 'default' : 'secondary'}>
                                                            {t.status}
                                                        </Badge>
                                                        {t.oracle_terminal_id && (
                                                            <Badge variant="outline" className="bg-amber-500/10 text-amber-500 border-amber-500/20 font-mono text-[10px]">
                                                                Oracle WS: {t.oracle_terminal_id}
                                                            </Badge>
                                                        )}
                                                        {t.crypto_processor_enabled && (
                                                            <Badge variant="outline" className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 text-[10px]">
                                                                Crypto Enabled ({t.default_crypto_currency})
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <p className="font-mono text-xs text-muted-foreground mt-0.5">
                                                        S/N: {t.serial_number} • Model: {t.model}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Linked Account: <span className="font-medium">{t.account_label} ({t.account_number})</span>
                                                    </p>
                                                    {t.last_active_at && (
                                                        <p className="text-[10px] text-muted-foreground mt-0.5">
                                                            Last active: {new Date(t.last_active_at).toLocaleString()}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2 self-end sm:self-center">
                                                {t.status === 'active' && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => startEditing(t)}
                                                        className="h-8 text-xs"
                                                    >
                                                        <Settings className="mr-1 h-3.5 w-3.5" /> Config
                                                    </Button>
                                                )}
                                                {t.status === 'active' && t.oracle_terminal_id && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => startSimulation(t)}
                                                        className="h-8 text-xs bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/20"
                                                    >
                                                        <QrCode className="mr-1 h-3.5 w-3.5" /> Simulate
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleToggle(t.id)}
                                                    disabled={toggleForm.processing}
                                                    className="h-8 text-xs"
                                                >
                                                    {t.status === 'active' ? (
                                                        <>
                                                            <Pause className="mr-1 h-3.5 w-3.5" /> Deactivate
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Play className="mr-1 h-3.5 w-3.5" /> Activate
                                                        </>
                                                    )}
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => handleDelete(t.id)}
                                                    disabled={deleteForm.processing}
                                                    className="h-8 text-xs"
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </div>

                                        {/* Configuration Section */}
                                        {editingTerminalId === t.id && (
                                            <form onSubmit={(e) => handleSaveConfig(e, t.id)} className="border-t pt-4 mt-2 space-y-4">
                                                <h4 className="text-sm font-semibold flex items-center gap-2"><Settings className="size-4 text-primary" />Oracle POS & Crypto Configuration</h4>
                                                
                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`oracle_terminal_id-${t.id}`}>Oracle Workstation ID *</Label>
                                                        <Input
                                                            id={`oracle_terminal_id-${t.id}`}
                                                            placeholder="e.g. WS-101"
                                                            value={configForm.data.oracle_terminal_id}
                                                            onChange={e => configForm.setData('oracle_terminal_id', e.target.value)}
                                                            required
                                                        />
                                                    </div>

                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`crypto_enabled-${t.id}`}>Crypto Processor Mode *</Label>
                                                        <Select
                                                            value={configForm.data.crypto_processor_enabled ? 'true' : 'false'}
                                                            onValueChange={v => configForm.setData('crypto_processor_enabled', v === 'true')}
                                                        >
                                                            <SelectTrigger id={`crypto_enabled-${t.id}`}>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="false">Card Payments Only</SelectItem>
                                                                <SelectItem value="true">Enable Crypto Payments (QR Code)</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`crypto_currency-${t.id}`}>Default Cryptocurrency</Label>
                                                        <Select
                                                            value={configForm.data.default_crypto_currency}
                                                            onValueChange={v => configForm.setData('default_crypto_currency', v)}
                                                            disabled={!configForm.data.crypto_processor_enabled}
                                                        >
                                                            <SelectTrigger id={`crypto_currency-${t.id}`}>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="USDC">USDC (Stablecoin)</SelectItem>
                                                                <SelectItem value="USDT">USDT (Stablecoin)</SelectItem>
                                                                <SelectItem value="BTC">Bitcoin (BTC)</SelectItem>
                                                                <SelectItem value="ETH">Ethereum (ETH)</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`settlement_mode-${t.id}`}>Settlement Mode</Label>
                                                        <Select
                                                            value={configForm.data.settlement_mode}
                                                            onValueChange={v => configForm.setData('settlement_mode', v)}
                                                            disabled={!configForm.data.crypto_processor_enabled}
                                                        >
                                                            <SelectTrigger id={`settlement_mode-${t.id}`}>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="fiat">Convert to Fiat (EUR Payout)</SelectItem>
                                                                <SelectItem value="crypto">Keep in Crypto (Wallet Payout)</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`oracle_api_url-${t.id}`}>Oracle Simphony API URL (Endpoint)</Label>
                                                        <Input
                                                            id={`oracle_api_url-${t.id}`}
                                                            placeholder="https://micros-simphony.example.com/api/payment"
                                                            value={configForm.data.oracle_api_url}
                                                            onChange={e => configForm.setData('oracle_api_url', e.target.value)}
                                                        />
                                                    </div>

                                                    <div className="space-y-1.5">
                                                        <Label htmlFor={`oracle_api_key-${t.id}`}>Oracle API Auth Key / Token</Label>
                                                        <Input
                                                            id={`oracle_api_key-${t.id}`}
                                                            type="password"
                                                            placeholder="••••••••••••••••"
                                                            value={configForm.data.oracle_api_key}
                                                            onChange={e => configForm.setData('oracle_api_key', e.target.value)}
                                                        />
                                                    </div>
                                                </div>

                                                <div className="flex gap-2 justify-end">
                                                    <Button type="button" variant="outline" size="sm" onClick={() => setEditingTerminalId(null)}>Cancel</Button>
                                                    <Button type="submit" size="sm" disabled={configForm.processing}>Save Settings</Button>
                                                </div>
                                            </form>
                                        )}

                                        {/* Simulation Section */}
                                        {simulatingTerminalId === t.id && (
                                            <div className="border-t pt-4 mt-2 space-y-4 bg-slate-50 dark:bg-slate-900/40 p-4 rounded-lg">
                                                <h4 className="text-sm font-semibold flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                                                    <QrCode className="size-4" /> Simulate Oracle POS to Crypto Checkout
                                                </h4>
                                                
                                                {!pendingInvoice ? (
                                                    <div className="space-y-3">
                                                        <div className="space-y-1.5">
                                                            <Label>Bill Amount (EUR)</Label>
                                                            <Input
                                                                type="number"
                                                                step="0.01"
                                                                value={simAmount}
                                                                onChange={e => setSimAmount(e.target.value)}
                                                            />
                                                        </div>
                                                        <div className="flex gap-2">
                                                            <Button size="sm" onClick={handleCreateSimulationCharge}>
                                                                Initiate POS Payment
                                                            </Button>
                                                            <Button size="sm" variant="outline" onClick={() => setSimulatingTerminalId(null)}>
                                                                Cancel
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-4">
                                                        <div className="flex flex-col md:flex-row items-center gap-6 bg-background p-4 rounded-lg border">
                                                            <div className="bg-white p-3 rounded-lg border size-32 flex flex-col items-center justify-center gap-1 shadow-inner">
                                                                <QrCode className="size-20 text-slate-800" />
                                                                <span className="text-[10px] font-mono text-slate-600 uppercase font-semibold">{pendingInvoice.crypto_currency}</span>
                                                            </div>

                                                            <div className="space-y-1.5 text-sm flex-1">
                                                                <p className="font-semibold text-base">Scan to Pay on PAX A77</p>
                                                                <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                                                    <span className="text-muted-foreground">Reference:</span>
                                                                    <span className="font-mono">{pendingInvoice.reference}</span>
                                                                    <span className="text-muted-foreground">Fiat Total:</span>
                                                                    <span className="font-semibold">€{Number(pendingInvoice.fiat_amount).toFixed(2)}</span>
                                                                    <span className="text-muted-foreground">Crypto Amount:</span>
                                                                    <span className="font-mono text-emerald-600 font-semibold">{pendingInvoice.crypto_amount} {pendingInvoice.crypto_currency}</span>
                                                                    <span className="text-muted-foreground">Receive Address:</span>
                                                                    <span className="font-mono truncate select-all" title={pendingInvoice.crypto_address}>{pendingInvoice.crypto_address}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="flex gap-2">
                                                            <Button 
                                                                size="sm" 
                                                                onClick={handlePaySimulationCharge}
                                                                disabled={simulatingPayment}
                                                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                                            >
                                                                {simulatingPayment ? 'Confirming Blockchain transfer...' : 'Confirm Simulated Payment'}
                                                            </Button>
                                                            <Button 
                                                                size="sm" 
                                                                variant="outline" 
                                                                onClick={() => setPendingInvoice(null)}
                                                            >
                                                                Go Back
                                                            </Button>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        {/* Recent Transactions */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Reader Transactions Log</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {pos_transactions.length === 0 && (
                                    <p className="text-sm text-muted-foreground py-4 text-center">
                                        No card transactions recorded yet.
                                    </p>
                                )}
                                {pos_transactions.map(pt => (
                                    <div key={pt.id} className="flex items-center justify-between rounded-lg border p-4">
                                        <div className="flex items-start gap-3">
                                            {pt.payment_type === 'crypto' ? (
                                                <Coins className="size-7 text-emerald-500 mt-0.5 animate-pulse" />
                                            ) : (
                                                <CreditCard className="size-7 text-muted-foreground mt-0.5" />
                                            )}
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold text-sm">
                                                        {pt.payment_type === 'crypto' ? (
                                                            <span>Crypto ({pt.crypto_amount} {pt.crypto_currency})</span>
                                                        ) : (
                                                            <span>{pt.card_brand} **** {pt.card_last4}</span>
                                                        )}
                                                    </span>
                                                    <Badge
                                                        variant={
                                                            pt.status === 'completed'
                                                                ? 'default'
                                                                : pt.status === 'refunded'
                                                                ? 'secondary'
                                                                : 'destructive'
                                                        }
                                                    >
                                                        {pt.status}
                                                    </Badge>
                                                </div>
                                                <p className="font-mono text-xs text-muted-foreground mt-0.5">
                                                    Ref: {pt.terminal_reference} • Device: {pt.terminal_serial}
                                                </p>
                                                <p className="text-[10px] text-muted-foreground mt-0.5">
                                                    {new Date(pt.created_at).toLocaleString()} • Method: {pt.payment_type === 'crypto' ? 'On-chain' : pt.payment_method}
                                                </p>
                                                {pt.tx_hash && (
                                                    <p className="font-mono text-[9px] text-emerald-600 dark:text-emerald-400 mt-0.5 truncate max-w-[200px]" title={pt.tx_hash}>
                                                        Tx: {pt.tx_hash}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="text-right flex flex-col items-end gap-2">
                                            <p className="font-semibold text-sm">
                                                €{Number(pt.amount).toFixed(2)}
                                            </p>
                                            {pt.status === 'completed' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 text-xs text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20"
                                                    onClick={() => handleRefund(pt.id)}
                                                    disabled={refundForm.processing}
                                                >
                                                    <RotateCcw className="mr-1 h-3 w-3" /> Refund
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
