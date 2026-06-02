import { Head, useForm } from '@inertiajs/react';
import { Tablet, CreditCard, Play, Pause, Trash2, RotateCcw, ShieldCheck } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

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
    label: string;
    model: string;
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
        model: 'Stripe S700',
        pairing_code: '',
    });

    const toggleForm = useForm({});
    const deleteForm = useForm({});
    const refundForm = useForm({});

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
                                    <div key={t.id} className="flex flex-col sm:flex-row sm:items-center justify-between rounded-lg border p-4 gap-4">
                                        <div className="flex items-start gap-3">
                                            <Tablet className="size-8 text-primary mt-0.5" />
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold text-sm">{t.label || 'Unnamed Terminal'}</span>
                                                    <Badge variant={t.status === 'active' ? 'default' : 'secondary'}>
                                                        {t.status}
                                                    </Badge>
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
                                        <div className="flex items-center gap-2 self-end sm:self-center">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleToggle(t.id)}
                                                disabled={toggleForm.processing}
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
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
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
                                            <CreditCard className="size-7 text-muted-foreground mt-0.5" />
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold text-sm">
                                                        {pt.card_brand} **** {pt.card_last4}
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
                                                    {new Date(pt.created_at).toLocaleString()} • Method: {pt.payment_method}
                                                </p>
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
